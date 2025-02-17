<?php

$config['rules'] = [
    karmabunny\kb\rules\RequiredRule::class,
    karmabunny\kb\rules\AllInArrayRule::class,
    karmabunny\kb\rules\AllMatchRule::class,
    karmabunny\kb\rules\AllUniqueRule::class,
    karmabunny\kb\rules\BinaryRule::class,
    karmabunny\kb\rules\DateRangeRule::class,
    karmabunny\kb\rules\EmailRule::class,
    karmabunny\kb\rules\InArrayRule::class,
    karmabunny\kb\rules\Ipv4AddrOrCidrRule::class,
    karmabunny\kb\rules\Ipv4AddrRule::class,
    karmabunny\kb\rules\Ipv4CidrRule::class,
    karmabunny\kb\rules\LengthRule::class,
    karmabunny\kb\rules\MysqlDateRule::class,
    karmabunny\kb\rules\MysqlDateTimeRule::class,
    karmabunny\kb\rules\MysqlTimeRule::class,
    karmabunny\kb\rules\NumericRule::class,
    karmabunny\kb\rules\OneRequiredRule::class,
    karmabunny\kb\rules\PhoneRule::class,
    karmabunny\kb\rules\PositiveIntRule::class,
    karmabunny\kb\rules\ProseTextRule::class,
    karmabunny\kb\rules\RangeRule::class,
    karmabunny\kb\rules\RegexRule::class,

    // New password rule.
    Sprout\Helpers\Rules\PasswordRule::class,

    // Pdb/model based rules.
    Sprout\Helpers\Rules\AllInSetRule::class,
    Sprout\Helpers\Rules\AllInTableRule::class,
    Sprout\Helpers\Rules\InEnumRule::class,
    Sprout\Helpers\Rules\InTableRule::class,
    Sprout\Helpers\Rules\UniqueValueRule::class,
];
