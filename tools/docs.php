<?php

use Komma\Tbai\LROE\Expenses\JuridicPerson\ExpensesInvoice as JuridicPersonExpensesInvoice;
use Komma\Tbai\LROE\Expenses\SelfEmployed\ExpensesInvoice as SelfEmployedExpensesInvoice;
use Komma\Tbai\LROE\Expenses\SelfEmployed\ExpensesWithoutInvoice;
use Komma\Tbai\TicketBai;
use Komma\Tbai\TicketBaiCancel;
use Komma\Tbai\Zuzendu;
use Komma\Tbai\ZuzenduCancel;

include_once(__DIR__ . '/../vendor/autoload.php');

$docs = [
    "swagger" => "2.0",
    "info" => [
        "description" => "This document describes available JSON schemas for komma-softhouse/ticketbai PHP library",
        "version" => "0.5.0",
        "title" => "Komma/Ticketbai JSON schemas"
    ],
    "definitions" => [],
    "externalDocs" => [
        "description" => "Find out more about komma-softhouse/ticketbai",
        "url" => "https://github.com/komma-softhouse/tbai-php-lib"
    ]
];
$docs['definitions']['Ticketbai'] = TicketBai::docJson();
$docs['definitions']['TicketbaiCancel'] = TicketBaiCancel::docJson();
$docs['definitions']['ExpensesInvoice (Juridic Person)'] = JuridicPersonExpensesInvoice::docJson();
$docs['definitions']['ExpensesInvoice (Self Employed)'] = SelfEmployedExpensesInvoice::docJson();
$docs['definitions']['ExpensesWithoutInvoice (Self Employed)'] = ExpensesWithoutInvoice::docJson();
$docs['definitions']['Zuzendu'] = Zuzendu::docJson();
$docs['definitions']['ZuzenduCancel'] = ZuzenduCancel::docJson();

file_put_contents(__DIR__ . '/../docs/swagger/swagger.json', json_encode($docs, JSON_PRETTY_PRINT));
