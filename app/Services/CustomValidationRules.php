<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Rules\NciRule;
use App\Rules\TelephoneSenegalRule;
use App\Enums\MessageEnumFr;

class CustomValidationRules
{
    public static function required($validator, $key, $value, $message = MessageEnumFr::REQUIRED) {
        if (empty($value)) {
            $validator->errors()->add($key, $message);
        }
    }

    public static function minLength($validator, $key, $value, $minLength, $message = MessageEnumFr::MINLENGTH) {
        if (strlen($value) < $minLength) {
            $validator->errors()->add($key, $message);
        }
    }

    public static function isMail($validator, $key, $value, $message = MessageEnumFr::ISEMAIL) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $validator->errors()->add($key, $message);
        }
    }

    public static function isSenegalPhone($validator, $key, $value, $message = MessageEnumFr::ISSENEGALPHONE) {
        $rule = new TelephoneSenegalRule();
        $rule->validate($key, $value, function ($msg) use ($validator, $key) {
            $validator->errors()->add($key, $msg);
        });
    }

    public static function isCNI($validator, $key, $value, $message = MessageEnumFr::ISCNI) {
        $rule = new NciRule();
        $rule->validate($key, $value, function ($msg) use ($validator, $key) {
            $validator->errors()->add($key, $msg);
        });
    }

    public static function numeric($validator, $key, $value, $message = MessageEnumFr::NUMERIC) {
        if (!is_numeric($value)) {
            $validator->errors()->add($key, $message);
        }
    }

    public static function min($validator, $key, $value, $min, $message = MessageEnumFr::MIN) {
        if ($value < $min) {
            $validator->errors()->add($key, $message);
        }
    }

    public static function max($validator, $key, $value, $max, $message = MessageEnumFr::MAX) {
        if ($value > $max) {
            $validator->errors()->add($key, $message);
        }
    }

    public static function in($validator, $key, $value, $options, $message = MessageEnumFr::IN) {
        if (!in_array($value, $options)) {
            $validator->errors()->add($key, $message);
        }
    }

    public static function uuid($validator, $key, $value, $message = MessageEnumFr::UUID) {
        if (strlen($value) != 36 || $value[8] != '-' || $value[13] != '-' || $value[18] != '-' || $value[23] != '-') {
            $validator->errors()->add($key, $message);
        }
    }

    public static function exists($validator, $key, $value, $table, $column, $message = MessageEnumFr::EXISTS) {
        if (!DB::table($table)->where($column, $value)->exists()) {
            $validator->errors()->add($key, $message);
        }
    }

    public static function unique($validator, $key, $value, $table, $column, $message = MessageEnumFr::UNIQUE) {
        if (DB::table($table)->where($column, $value)->exists()) {
            $validator->errors()->add($key, $message);
        }
    }
}