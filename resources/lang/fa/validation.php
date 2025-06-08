<?php

return [
    'required' => 'فیلد :attribute الزامی است.',
    'unique' => 'فیلد :attribute باید منحصر به فرد باشد.',
    'min' => [
        'numeric' => 'فیلد :attribute باید حداقل :min باشد.',
        'string' => 'فیلد :attribute باید حداقل :min کاراکتر باشد.',
    ],
    'max' => [
        'numeric' => 'فیلد :attribute نباید بیشتر از :max باشد.',
        'string' => 'فیلد :attribute نباید بیشتر از :max کاراکتر باشد.',
    ],
    'email' => 'فرمت :attribute نامعتبر است.',
    'confirmed' => 'تاییدیه :attribute مطابقت ندارد.',
    'in' => ':attribute انتخاب شده نامعتبر است.',
    'file' => 'فیلد :attribute باید یک فایل باشد.',
    'mimes' => 'فیلد :attribute باید یک فایل از نوع: :values باشد.',
    'exists' => 'فیلد :attribute انتخاب شده نامعتبر است.',
    'custom' => [
        'name' => [
            'required' => 'نام الزامی است.',
        ],
        'username' => [
            'required' => 'نام کاربری الزامی است.',
        ],
        'mobile' => [
            'required' => 'شماره موبایل الزامی است.',
        ],
        'password' => [
            'required' => 'رمز عبور الزامی است.',
        ],
        'payment_method' => [
            'in' => 'روش پرداخت انتخاب شده نامعتبر است.',
        ],
        'transaction_status' => [
            'in' => 'وضعیت تراکنش انتخاب شده نامعتبر است.',
        ],
        'is_covered_by_monthly_charge' => [
            'boolean' => 'فیلد پوشش توسط شارژ ماهانه باید true یا false باشد.',
        ],
        'images' => [
            'array' => 'فیلد تصاویر باید یک آرایه باشد.',
        ],
    ],
    'attributes' => [
        'name' => 'نام',
        'amount' => 'مقدار',
        'payment_method' => 'روش پرداخت',
        'distribution_method' => 'روش توزیع',
        'building_id' => 'ساختمان',
        'username' => 'نام کاربری',
        'mobile' => 'شماره موبایل',
        'password' => 'رمز عبور',
        'transaction_status' => 'وضعیت تراکنش',
        'is_covered_by_monthly_charge' => 'پوشش توسط شارژ ماهانه',
        'image' => 'تصویر',
        'images' => 'تصاویر',
        'user_id' => 'شناسه کاربر',
    ],
];
