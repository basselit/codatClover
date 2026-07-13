<?php

namespace Codatsoft\CodatClover\Types;

abstract class CLEndpoints
{
    const EMPLOYEE_ADD = "/v3/merchants/{MERCHANT_ID}/employees:post";
    const EMPLOYEE_EDIT = "/v3/merchants/{MERCHANT_ID}/employees/{EMPLOYEE_ID}:post";
    const EMPLOYEES = "/v3/merchants/{MERCHANT_ID}/employees?limit=1000:get";
    const EMPLOYEE = "/v3/merchants/{MERCHANT_ID}/employees/{EMPLOYEE_ID}?limit=1000:get";
    const DEVICES = "/v3/merchants/{MERCHANT_ID}/devices?limit=1000:get";
    const DEVICE = "/v3/merchants/{MERCHANT_ID}/devices/{DEVICE_ID}:get";
    const ITEMS = "/v3/merchants/{MERCHANT_ID}/items?limit=1000";
    const ORDERS_BY_EMPLOYEE = "/v3/merchants/{MERCHANT_ID}/orders?filter=employee.id={EMPLOYEE_ID}&expand&expand=employee&expand=lineItems&expand=payments&expand=payments.cardTransaction&expand=customers&expand=voids&forceRealTime=true&expand=payments.employee&limit={LIMIT}:get";
    const ORDERS_BY_IDS = "/v3/merchants/{MERCHANT_ID}/orders?filter={ORDERS_IDS}&expand=employee&expand=lineItems&expand=payments&expand=payments.cardTransaction&expand=customers&expand=voids&expand=payments.employee&limit=1000&forceRealTime=true:get";
    const ORDER_BY_ID = "/v3/merchants/{MERCHANT_ID}/orders/{ORDER_ID}?expand=employee&expand=lineItems&expand=payments&expand=payments.cardTransaction&expand=customers&expand=voids&expand=payments.employee&limit=1000&expand=externalReferenceId&forceRealTime=true:get";
    const ORDERS_BY_DATE = "/v3/merchants/{MERCHANT_ID}/orders?filter=clientCreatedTime>={START_TIMESTAMP}&filter=clientCreatedTime<={END_TIMESTAMP}&expand=employee&expand=lineItems&expand=payments&expand=payments.cardTransaction&expand=customers&expand=voids&expand=payments.employee&limit=1000&forceRealTime=true:get";
    const PAYMENTS_BY_DATE = "/v3/merchants/{MERCHANT_ID}/payments?filter=clientCreatedTime>={START_TIMESTAMP}&filter=clientCreatedTime<={END_TIMESTAMP}&expand=employee&expand=lineItems&expand=cardTransaction&expand=card&expand=customers&expand=tender&expand=cardTransaction.paymentRef&expand=cardTransaction.extra&limit=1000&forceRealTime=true:get";
    const ORDERS_BY_EMPLOYEE_DATES = "/v3/merchants/{MERCHANT_ID}/orders?filter=employee.id={EMPLOYEE_ID}&filter=clientCreatedTime>={START_TIMESTAMP}&filter=clientCreatedTime<={END_TIMESTAMP}&expand=employee&expand=lineItems&expand=payments&expand=payments.cardTransaction&expand=customers&expand=voids&expand=payments.employee&limit=1000&forceRealTime=true";

}
