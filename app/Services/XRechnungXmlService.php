<?php

namespace App\Services;

use Config\Wum;
use Easybill\ZUGFeRD2\Builder;
use Easybill\ZUGFeRD2\Model\Amount;
use Easybill\ZUGFeRD2\Model\CreditorFinancialAccount;
use Easybill\ZUGFeRD2\Model\CreditorFinancialInstitution;
use Easybill\ZUGFeRD2\Model\CrossIndustryInvoice;
use Easybill\ZUGFeRD2\Model\DateTime;
use Easybill\ZUGFeRD2\Model\DocumentContextParameter;
use Easybill\ZUGFeRD2\Model\DocumentLineDocument;
use Easybill\ZUGFeRD2\Model\ExchangedDocument;
use Easybill\ZUGFeRD2\Model\ExchangedDocumentContext;
use Easybill\ZUGFeRD2\Model\HeaderTradeAgreement;
use Easybill\ZUGFeRD2\Model\HeaderTradeDelivery;
use Easybill\ZUGFeRD2\Model\HeaderTradeSettlement;
use Easybill\ZUGFeRD2\Model\Id;
use Easybill\ZUGFeRD2\Model\LineTradeAgreement;
use Easybill\ZUGFeRD2\Model\LineTradeDelivery;
use Easybill\ZUGFeRD2\Model\LineTradeSettlement;
use Easybill\ZUGFeRD2\Model\Quantity;
use Easybill\ZUGFeRD2\Model\ReferencedDocument;
use Easybill\ZUGFeRD2\Model\SupplyChainEvent;
use Easybill\ZUGFeRD2\Model\SupplyChainTradeLineItem;
use Easybill\ZUGFeRD2\Model\SupplyChainTradeTransaction;
use Easybill\ZUGFeRD2\Model\TradeAddress;
use Easybill\ZUGFeRD2\Model\TradeContact;
use Easybill\ZUGFeRD2\Model\TradeParty;
use Easybill\ZUGFeRD2\Model\TradePaymentTerms;
use Easybill\ZUGFeRD2\Model\TradePrice;
use Easybill\ZUGFeRD2\Model\TradeProduct;
use Easybill\ZUGFeRD2\Model\TradeSettlementHeaderMonetarySummation;
use Easybill\ZUGFeRD2\Model\TradeSettlementLineMonetarySummation;
use Easybill\ZUGFeRD2\Model\TradeSettlementPaymentMeans;
use Easybill\ZUGFeRD2\Model\TradeTax;
use Easybill\ZUGFeRD2\Model\UniversalCommunication;

/**
 * Build Factur-X / EN 16931 XML for outgoing invoices (Kleinunternehmer §19).
 */
class XRechnungXmlService
{
    public function buildFromPayload(array $payload, string $invoiceNumber, ?string $correctionOf = null): string
    {
        $wum      = config('Wum');
        $customer = $payload['customer'];
        $dateYmd  = $this->germanDateToYmd((string) $payload['date']);
        $total    = $this->formatAmount((int) $payload['totalCents']);

        $invoice = new CrossIndustryInvoice();

        $context = new ExchangedDocumentContext();
        $context->documentContextParameter = DocumentContextParameter::create(Builder::GUIDELINE_SPECIFIED_DOCUMENT_CONTEXT_ID_EN16931);
        $invoice->exchangedDocumentContext = $context;

        $doc = new ExchangedDocument();
        $doc->id            = $invoiceNumber;
        $doc->typeCode      = '380';
        $doc->issueDateTime = DateTime::create(102, $dateYmd);
        $invoice->exchangedDocument = $doc;

        $seller = $this->buildSellerParty($wum);
        $buyer  = $this->buildBuyerParty($customer);

        $transaction = new SupplyChainTradeTransaction();
        $transaction->lineItems = $this->buildLineItems($payload['lineItems'] ?? [], $seller);

        $agreement = new HeaderTradeAgreement();
        $agreement->buyerReference    = (string) ($customer['customer_number'] ?? '');
        $agreement->sellerTradeParty   = $seller;
        $agreement->buyerTradeParty    = $buyer;
        $transaction->applicableHeaderTradeAgreement = $agreement;

        $delivery = new HeaderTradeDelivery();
        $event    = new SupplyChainEvent();
        $event->occurrenceDateTime = DateTime::create(102, $dateYmd);
        $delivery->actualDeliverySupplyChainEvent = $event;
        $transaction->applicableHeaderTradeDelivery = $delivery;

        $settlement = new HeaderTradeSettlement();
        $settlement->taxCurrencyCode     = 'EUR';
        $settlement->invoiceCurrencyCode = 'EUR';
        $settlement->specifiedTradeSettlementPaymentMeans = [$this->buildPaymentMeans($wum)];
        $settlement->tradeTaxes = [
            TradeTax::create(
                'VAT',
                Amount::create('0.00'),
                Amount::create($total),
                null,
                null,
                'E',
                '0.00',
                $wum->kleinunternehmerNote
            ),
        ];

        $paymentTerms = new TradePaymentTerms();
        $paymentTerms->dueDateDateTime = DateTime::create(102, $dateYmd);
        $settlement->specifiedTradePaymentTerms = [$paymentTerms];

        $summation = new TradeSettlementHeaderMonetarySummation();
        $summation->lineTotalAmount      = Amount::create($total);
        $summation->chargeTotalAmount    = Amount::create('0.00');
        $summation->allowanceTotalAmount = Amount::create('0.00');
        $summation->taxBasisTotalAmount  = [Amount::create($total)];
        $summation->taxTotalAmount       = [Amount::create('0.00', 'EUR')];
        $summation->grandTotalAmount     = [Amount::create($total)];
        $summation->totalPrepaidAmount   = Amount::create('0.00');
        $summation->duePayableAmount     = Amount::create($total);
        $settlement->specifiedTradeSettlementHeaderMonetarySummation = $summation;

        if ($correctionOf !== null && $correctionOf !== '') {
            $settlement->invoiceReferencedDocument = [ReferencedDocument::create($correctionOf)];
        }

        $transaction->applicableHeaderTradeSettlement = $settlement;
        $invoice->supplyChainTradeTransaction         = $transaction;

        return Builder::create()->transform($invoice);
    }

    /**
     * @param list<array<string, mixed>> $lineItems
     * @return list<SupplyChainTradeLineItem>
     */
    private function buildLineItems(array $lineItems, TradeParty $seller): array
    {
        $out  = [];
        $line = 1;
        foreach ($lineItems as $item) {
            if (!empty($item['is_optional'])) {
                continue;
            }
            $qty        = (int) ($item['qty'] ?? 0);
            $priceCents = (int) ($item['price_cents'] ?? 0);
            $totalCents = (int) ($item['total_cents'] ?? ($qty * $priceCents));
            if ($qty <= 0 && $totalCents <= 0) {
                continue;
            }

            $unitCode = $this->mapUnitCode((string) ($item['unit'] ?? DocumentSessionService::DEFAULT_UNIT));
            $qtyStr   = number_format(max(1, $qty), 4, '.', '');
            $ep       = $this->formatAmount($priceCents);
            $gp       = $this->formatAmount($totalCents);

            $row = new SupplyChainTradeLineItem();
            $row->associatedDocumentLineDocument = DocumentLineDocument::create((string) $line);

            $product = new TradeProduct();
            $product->name = (string) ($item['title'] ?? 'Leistung');
            if (!empty($item['position_number'])) {
                $product->sellerAssignedID = (string) $item['position_number'];
            }
            $row->specifiedTradeProduct = $product;

            $agreement = new LineTradeAgreement();
            $agreement->netPrice = TradePrice::create($ep, Quantity::create('1.0000', $unitCode));
            $agreement->itemSellerTradeParty = $seller;
            $row->tradeAgreement = $agreement;

            $delivery = new LineTradeDelivery();
            $delivery->billedQuantity = Quantity::create($qtyStr, $unitCode);
            $row->delivery = $delivery;

            $lineSettlement = new LineTradeSettlement();
            $lineSettlement->tradeTax = [
                TradeTax::create('VAT', null, null, null, null, 'E', '0.00'),
            ];
            $lineSettlement->monetarySummation = TradeSettlementLineMonetarySummation::create($gp);
            $row->specifiedLineTradeSettlement = $lineSettlement;

            $out[] = $row;
            $line++;
        }

        return $out;
    }

    private function buildSellerParty(Wum $wum): TradeParty
    {
        $party = new TradeParty();
        $party->name        = $wum->companyName;
        $party->description = 'Kleinunternehmer gemäß § 19 UStG';

        $contact = new TradeContact();
        $contact->personName = trim(str_replace('Inhaber: ', '', $wum->companyOwner));
        $phone               = new UniversalCommunication();
        $phone->completeNumber = preg_replace('/^Telefon:\s*/', '', $wum->companyPhone) ?: null;
        $contact->telephoneUniversalCommunication = $phone;
        $email = new UniversalCommunication();
        $email->uriid = Id::create($wum->companyEmailPlain);
        $contact->emailURIUniversalCommunication = $email;
        $party->definedTradeContact = [$contact];

        $address = new TradeAddress();
        $address->postcodeCode = $this->extractPostalCode($wum->companyCity);
        $address->lineOne      = $wum->companyStreet;
        $address->cityName     = $this->extractCityName($wum->companyCity);
        $address->countryID    = 'DE';
        $party->postalTradeAddress = $address;

        $uri = new UniversalCommunication();
        $uri->uriid = Id::create($wum->companyEmailPlain, 'EM');
        $party->uriUniversalCommunication = $uri;

        $taxId = $this->extractTaxNumber($wum->companyTaxNumber);
        if ($taxId !== '') {
            $party->taxRegistrations = [\Easybill\ZUGFeRD2\Model\TaxRegistration::create($taxId, 'FC')];
        }

        return $party;
    }

    /**
     * @param array<string, mixed> $customer
     */
    private function buildBuyerParty(array $customer): TradeParty
    {
        $party = new TradeParty();
        $name  = trim((string) ($customer['company'] ?? ''));
        if ($name === '') {
            $name = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        }
        $party->name = $name;

        $address = new TradeAddress();
        $address->postcodeCode = (string) ($customer['postal_code'] ?? '');
        $address->lineOne      = (string) ($customer['street'] ?? '');
        $address->cityName     = (string) ($customer['city'] ?? '');
        $address->countryID    = 'DE';
        $party->postalTradeAddress = $address;

        $email = trim((string) ($customer['email'] ?? ''));
        if ($email !== '') {
            $uri = new UniversalCommunication();
            $uri->uriid = Id::create($email, 'EM');
            $party->uriUniversalCommunication = $uri;
        }

        return $party;
    }

    private function buildPaymentMeans(Wum $wum): TradeSettlementPaymentMeans
    {
        $means = new TradeSettlementPaymentMeans();
        $means->typeCode     = '58';
        $means->information  = 'SEPA credit transfer';

        $account = new CreditorFinancialAccount();
        $account->ibanId      = Id::create(str_replace(' ', '', $wum->companyIban));
        $account->accountName = $wum->companyName;
        $means->payeePartyCreditorFinancialAccount = $account;

        $institution = new CreditorFinancialInstitution();
        $institution->bicId = Id::create($wum->companyBic);
        $means->payeeSpecifiedCreditorFinancialInstitution = $institution;

        return $means;
    }

    private function mapUnitCode(string $unit): string
    {
        return match ($unit) {
            'Std.'  => 'HUR',
            'AW'    => 'HUR',
            'psch.' => 'C62',
            default => 'H87',
        };
    }

    private function formatAmount(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function germanDateToYmd(string $dmy): string
    {
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', trim($dmy), $m)) {
            return sprintf('%04d%02d%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        return date('Ymd');
    }

    private function extractPostalCode(string $companyCity): string
    {
        if (preg_match('/^(\d{4,5})\s+/', trim($companyCity), $m)) {
            return $m[1];
        }

        return '';
    }

    private function extractCityName(string $companyCity): string
    {
        if (preg_match('/^\d{4,5}\s+(.+)$/', trim($companyCity), $m)) {
            return $m[1];
        }

        return $companyCity;
    }

    private function extractTaxNumber(string $raw): string
    {
        if (preg_match('/Steuernummer:\s*(.+)$/i', $raw, $m)) {
            return trim($m[1]);
        }

        return trim($raw);
    }
}
