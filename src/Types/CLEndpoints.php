<?php

namespace Codatsoft\CodatClover\Types;

abstract class CLEndpoints
{
    const string EMPLOYEE_ADD = "/v3/merchants/{MERCHANT_ID}/employees:post";
    const string EMPLOYEE_EDIT = "/v3/merchants/{MERCHANT_ID}/employees/{EMPLOYEE_ID}:post";
    const string EMPLOYEES = "/v3/merchants/{MERCHANT_ID}/employees?limit=1000:get";
    const string EMPLOYEE = "/v3/merchants/{MERCHANT_ID}/employees/{EMPLOYEE_ID}?limit=1000:get";
    const string DEVICES = "/v3/merchants/{MERCHANT_ID}/devices?limit=1000:get";
    const string DEVICE = "/v3/merchants/{MERCHANT_ID}/devices/{DEVICE_ID}:get";
    // app-scoped (NOT merchant-scoped): requires the app's OAuth token, and
    // {DEVICE_ID} is clover's device uuid, not the serial
    const string DEVICE_NOTIFICATION = "/v3/apps/{APP_ID}/devices/{DEVICE_ID}/notifications:post";
    const string ITEMS = "/v3/merchants/{MERCHANT_ID}/items?limit=1000";
    const string ORDERS_BY_EMPLOYEE = "/v3/merchants/{MERCHANT_ID}/orders?filter=employee.id={EMPLOYEE_ID}&expand&expand=employee&expand=lineItems&expand=payments&expand=payments.cardTransaction&expand=customers&expand=voids&forceRealTime=true&expand=payments.employee&limit={LIMIT}:get";
    const string ORDERS_BY_IDS = "/v3/merchants/{MERCHANT_ID}/orders?filter={ORDERS_IDS}&expand=employee&expand=lineItems&expand=payments&expand=payments.cardTransaction&expand=customers&expand=voids&expand=payments.employee&limit=1000&forceRealTime=true:get";
    const string ORDERS_BY_IDS_CARDS = "/v3/merchants/{MERCHANT_ID}/orders?filter={ORDERS_IDS}&expand=payments&expand=payments.cardTransaction&expand=customers&limit=1000&forceRealTime=true:get";
    const string ORDER_BY_ID = "/v3/merchants/{MERCHANT_ID}/orders/{ORDER_ID}?expand=employee&expand=lineItems&expand=payments&expand=payments.cardTransaction&expand=customers&expand=voids&expand=payments.employee&limit=1000&expand=externalReferenceId&forceRealTime=true:get";
    const string ORDERS_BY_DATE = "/v3/merchants/{MERCHANT_ID}/orders?filter=clientCreatedTime>={START_TIMESTAMP}&filter=clientCreatedTime<={END_TIMESTAMP}&expand=employee&expand=lineItems&expand=payments&expand=payments.cardTransaction&expand=customers&expand=voids&expand=payments.employee&limit=1000&forceRealTime=true:get";
    const string PAYMENTS_BY_DATE = "/v3/merchants/{MERCHANT_ID}/payments?filter=clientCreatedTime>={START_TIMESTAMP}&filter=clientCreatedTime<={END_TIMESTAMP}&expand=employee&expand=lineItems&expand=cardTransaction&expand=card&expand=customers&expand=tender&expand=cardTransaction.paymentRef&expand=cardTransaction.extra&limit=1000&forceRealTime=true:get";
    const string ORDERS_BY_EMPLOYEE_DATES = "/v3/merchants/{MERCHANT_ID}/orders?filter=employee.id={EMPLOYEE_ID}&filter=clientCreatedTime>={START_TIMESTAMP}&filter=clientCreatedTime<={END_TIMESTAMP}&expand=employee&expand=lineItems&expand=payments&expand=payments.cardTransaction&expand=customers&expand=voids&expand=payments.employee&limit=1000&forceRealTime=true";
    const string CUSTOMERS = "/v3/merchants/{MERCHANT_ID}/customers?expand=addresses&expand=emailAddresses&expand=phoneNumbers&expand=cards&expand=metadata&offset={OFFSET}&limit=1000:get";

}
