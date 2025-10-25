<?php

namespace App\Utils;

class Validator
{
    public static function validate($data, $rules)
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $ruleList = explode('|', $rule);

            foreach ($ruleList as $r) {
                if ($r === 'required' && (!isset($data[$field]) || trim($data[$field]) === '')) {
                    $errors[$field] = ucfirst($field) . ' is required';
                    break;
                }

                if (strpos($r, 'min:') === 0 && isset($data[$field])) {
                    $min = (int)substr($r, 4);
                    if (strlen($data[$field]) < $min) {
                        $errors[$field] = ucfirst($field) . " must be at least $min characters";
                        break;
                    }
                }

                if ($r === 'email' && isset($data[$field])) {
                    if (!filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                        $errors[$field] = ucfirst($field) . ' must be a valid email address';
                        break;
                    }
                }

                if ($r === 'numeric' && isset($data[$field])) {
                    if (!is_numeric($data[$field])) {
                        $errors[$field] = ucfirst($field) . ' must be a number';
                        break;
                    }
                }

                if ($r === 'boolean' && isset($data[$field])) {
                    $value = $data[$field];
                    $isBool = is_bool($value);
                    $is01 = $value === 0 || $value === 1 || $value === '0' || $value === '1';
                    $isTrueFalse = is_string($value) && in_array(strtolower($value), ['true','false'], true);
                    if (!($isBool || $is01 || $isTrueFalse)) {
                        $errors[$field] = ucfirst($field) . ' must be a boolean';
                        break;
                    }
                }
            }
        }

        return $errors;
    }

    public static function sanitize($data)
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
}
