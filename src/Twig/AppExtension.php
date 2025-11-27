<?php

namespace App\Twig;

use App\Entity\API;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use DateTime;

class AppExtension extends AbstractExtension {

    /**
     * 
     * @return string
     */
    public function getName() {
        return 'twig_extension';
    }

    /**
     * 
     * @return type 
     */
    public function getFilters() {

        return array(
            new TwigFilter('boolean_format', array($this, 'booleanFormat')),
            new TwigFilter('currency_format', array($this, 'currencyFormat')),
            new TwigFilter('decode', array($this, 'stringDecode')),
            new TwigFilter('escape_json', array($this, 'escapeJsonString')),
            new TwigFilter('money_format', array($this, 'moneyFormat')),
            new TwigFilter('percentage_format', array($this, 'percentageFormat')),
            new TwigFilter('repeat', array($this, 'repeatString')),
            new TwigFilter('str_pad', array($this, 'strPad')),
            new TwigFilter('clear_format', array($this, 'clearFormat')),
            new TwigFilter('html_format', array($this, 'htmlFormat')),
            new TwigFilter('date_diff', array($this, 'dateDiff')),
            new TwigFilter('hex_opacity', array($this, 'changeHexOpacity')),
            new TwigFilter('encrypt', array($this, 'encrypt')),
            new TwigFilter('truncate', array($this, 'truncate')),
            new TwigFilter('__custom_parameters_auditoria', array($this, 'parametersAuditoria')),
        );
    }

    /**
     * 
     * @param type $string
     * @param type $count
     * @return type
     */
    public function repeatString($string, $count) {

        return str_repeat($string, $count);
    }

    /**
     * 
     * @param type $number
     * @param type $decimals
     * @param type $simbol
     * @param type $decPoint
     * @param type $thousandsSep
     * @param type $exchageRate
     * @return string
     */
    public function moneyFormat($number, $decimals = 2, $simbol = '$', $decPoint = ',', $thousandsSep = '.', $exchageRate = 1) {

        if (empty($number)) {
            return $number;
        }

        $valued = $number * $exchageRate;

        $price = number_format($valued, $decimals, $decPoint, $thousandsSep);

        $price = $simbol . ' ' . $price;

        return $price;
    }

    /**
     * 
     * @param type $number
     * @return string
     */
    public function percentageFormat($number, $decimals = 2) {
        if ($number != null) {
            return rtrim(rtrim(number_format(round($number, $decimals), $decimals, ',', '.'), "0"), ',') . ' %';
        }

        return null;
    }

    /**
     * 
     * @param type $text
     * @return type
     */
    public function clearFormat($text) {

        if ($text != null) {
            return html_entity_decode(strip_tags($text));
        }

        return null;
    }

    /**
     * 
     * @param type $text
     * @return type
     */
    public function htmlFormat($text) {
        if ($text != null) {
            return html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
        }

        return null;
    }

    /**
     * 
     * @param booelan $boolean
     * @return string
     */
    public function booleanFormat($boolean) {
        return $boolean ? 'Si' : 'No';
    }

    /**
     * 
     * @param type $number
     * @param type $decimals
     * @param type $decPoint
     * @param type $thousandsSep
     * @return type
     */
    public function currencyFormat($number, $decimals = 2, $decPoint = ',', $thousandsSep = '.') {
        $currency = number_format($number, $decimals, $decPoint, $thousandsSep);

        return $currency;
    }

    /**
     * 
     * @param type $input
     * @param type $pad_length
     * @param type $pad_string
     * @param type $pad_type
     * @return type
     */
    public function strPad($input, $pad_length = 6, $pad_string = "0", $pad_type = STR_PAD_LEFT) {
        return $input == null ? null : str_pad($input, $pad_length, $pad_string, $pad_type);
    }

    /**
     * 
     * @param type $string
     * @return type
     */
    public function stringDecode($string) {
        return htmlentities(html_entity_decode($string, ENT_QUOTES), ENT_QUOTES);
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    public function escapeJsonString($value) {
        $decodedValue = $this->stringDecode($value);

        $escapers = array("\\", "/", "\"", "\r\n", "\n", "\r", "\t", "\x08", "\x0c");

        $replacements = array("\\\\", "\\/", "\\\"", "<br />", "<br />", "<br />", "&nbsp;&nbsp;&nbsp;&nbsp;", "\\f", "\\b");

        $result = str_replace($escapers, $replacements, $decodedValue);

        // Reemplazamos comillas dobles por comillas simples
        $result = str_replace('"', "'", $result);

        // Eliminamos las barras invertidas antes de las barras normales
        $result = str_replace('\\/', '/', $result);

        // Eliminamos comillas dobles escapadas
        $result = str_replace('\"', "'", $result);

        // Reemplazamos comillas dobles anidadas
        $result = preg_replace('/"{2,}/', "'", $result);

        // Eliminamos caracteres de control
        $result = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $result);

        // Eliminamos espacios múltiples
        $result = preg_replace('/\s+/', ' ', $result);

        return $result;
    }

    /**
     * 
     * @param type $from
     * @param type $to
     * @param type $format
     */
    public function dateDiff($from, $to, $format) {
        $d1 = new DateTime($from);
        $d2 = new DateTime($to);

        $diff = $d2->diff($d1);

        echo $diff->format($format);
    }

    /**
     * 
     * @param type $color
     * @param real $opacity
     * @return string
     */
    public function changeHexOpacity($color, $opacity = false) {
        $default = 'rgb(0,0,0)';

        // Return default if no color provided
        if (empty($color)) {
            return $default;
        }

        // Sanitize $color if "#" is provided 
        if ($color[0] == '#') {
            $color = substr($color, 1);
        }

        // Check if color has 6 or 3 characters and get values
        if (strlen($color) == 6) {
            $hex = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
        } elseif (strlen($color) == 3) {
            $hex = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
        } else {
            return $default;
        }

        // Convert hexadec to rgb
        $rgb = array_map('hexdec', $hex);

        // Check if opacity is set(rgba or rgb)
        if ($opacity) {
            if (abs($opacity) > 1) {
                $opacity = 1.0;
            }

            $output = 'rgba(' . implode(",", $rgb) . ',' . $opacity . ')';
        } else {
            $output = 'rgb(' . implode(",", $rgb) . ')';
        }

        // Return rgb(a) color string
        return $output;
    }

    public function getFunctions() {
        return array(
            'file_exists' => new \Twig\TwigFunction('file_exists', 'file_exists'),
        );
    }

    public function encrypt($param) {
        return API::encrypt($param);
    }

    /**
     * Trunca la longitud de un string
     * 
     * @param string $string
     * @param int $limit
     * @param string $leyenda
     * @return string 
     */
    public function truncate($string, $limit = 100, $leyenda = '...') {

        $lenString = strlen($string);

        if ($lenString > $limit) {
            $string = mb_substr($string, 0, $limit, "UTF-8");
            return $string . $leyenda;
        } else {
            return $string;
        }
    }

    /**
     * 
     * @param type $array
     * @param type $string
     * @return type
     */
    public function parametersAuditoria($array, $string = '') {

        $rrr = '';

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $rrr .= '<li>' . $key . ': </li><ul>' . $this->parametersAuditoria($value, $string) . '</ul>';
            } else {
                $rrr .= '<li>' . $key . ': ' . $value . '</li>';
            }
        }

        return $rrr;
    }

}
