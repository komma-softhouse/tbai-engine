<?php

namespace Komma\Tbai\LROE\Expenses;

use Komma\Tbai\LROE\Expenses\Interfaces\ExpensesInvoice as InterfacesExpensesInvoice;
use Komma\Tbai\LROE\Expenses\JuridicPerson\ExpensesInvoice as JuridicPersonExpensesInvoice;
use Komma\Tbai\LROE\Expenses\SelfEmployed\ExpensesInvoice as SelfEmployedExpensesInvoice;

class ExpensesInvoiceFactory
{
    public static function createFromJson(array $jsonData): InterfacesExpensesInvoice
    {
        if (array_key_exists('selfEmployed', $jsonData) && $jsonData['selfEmployed']) {
            return SelfEmployedExpensesInvoice::createFromJson($jsonData);
        }

        return JuridicPersonExpensesInvoice::createFromJson($jsonData);
    }
}
