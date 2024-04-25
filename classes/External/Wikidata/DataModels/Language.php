<?php
/**
 * @file classes/External/Wikidata/DataModels/Language.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Language
 * @brief Language on Wikidata, mapped between OJS and Wikidata
 * @see https://www.loc.gov/standards/iso639-2/php/code_list.php
 */

namespace APP\plugins\generic\citationManager\classes\External\Wikidata\DataModels;

class Language
{
    /** @var string Default language if none found */
    public string $defaultLanguage = 'en';

    /**
     * Get the correct language code
     *
     * @param string $locale
     * @return string
     */
    public function getLanguageCode(string $locale): string
    {
        $languages = $this->languages();

        if(!empty($locale)){
            if(!empty($languages[strtolower($locale)])){
                return $languages[strtolower($locale)];
            }
        }

        return $this->defaultLanguage;
    }

    /**
     * List of languages, mapped between OJS and Wikidata
     *
     * @return string[]
     */
    private function languages(): array
    {
        return [
            'aa' => 'aa',
            'ab' => 'ab',
            'ae' => 'ae',
            'af' => 'af',
            'ak' => 'ak',
            'am' => 'am',
            'an' => 'an',
            'ar' => 'ar',
            'as' => 'as',
            'av' => 'av',
            'ay' => 'ay',
            'az' => 'az',
            'ba' => 'ba',
            'be@cyrillic' => 'be',
            'bg' => 'bg',
            'bh' => 'bh',
            'bi' => 'bi',
            'bm' => 'bm',
            'bn' => 'bn',
            'bo' => 'bo',
            'br' => 'br',
            'bs' => 'bs',
            'ca' => 'ca',
            'ce' => 'ce',
            'ch' => 'ch',
            'ckb' => 'ku',
            'co' => 'co',
            'cr' => 'cr',
            'cs' => 'cs',
            'cu' => 'cu',
            'cv' => 'cv',
            'cy' => 'cy',
            'da' => 'da',
            'de' => 'de',
            'dv' => 'dv',
            'dz' => 'dz',
            'ee' => 'ee',
            'el' => 'el',
            'en' => 'en',
            'eo' => 'eo',
            'es_MX' => 'es',
            'es' => 'es',
            'et' => 'et',
            'eu' => 'eu',
            'fa' => 'fa',
            'ff' => 'ff',
            'fi' => 'fi',
            'fj' => 'fj',
            'fo' => 'fo',
            'fr_CA' => 'fr',
            'fr_FR' => 'fr',
            'fy' => 'fy',
            'ga' => 'ga',
            'gd' => 'gd',
            'gl' => 'gl',
            'gn' => 'gn',
            'gu' => 'gu',
            'gv' => 'gv',
            'ha' => 'ha',
            'he' => 'he',
            'hi' => 'hi',
            'ho' => 'ho',
            'hr' => 'hr',
            'ht' => 'ht',
            'hu' => 'hu',
            'hy' => 'hy',
            'hz' => 'hz',
            'ia' => 'ia',
            'id' => 'id',
            'ie' => 'ie',
            'ig' => 'ig',
            'ii' => 'ii',
            'ik' => 'ik',
            'io' => 'io',
            'is' => 'is',
            'it' => 'it',
            'iu' => 'iu',
            'ja' => 'ja',
            'jv' => 'jv',
            'ka' => 'ka',
            'kg' => 'kg',
            'ki' => 'ki',
            'kj' => 'kj',
            'kk' => 'kk',
            'kl' => 'kl',
            'km' => 'km',
            'kn' => 'kn',
            'ko' => 'ko',
            'kr' => 'kr',
            'ks' => 'ks',
            'ku' => 'ku',
            'kv' => 'kv',
            'kw' => 'kw',
            'ky' => 'ky',
            'la' => 'la',
            'lb' => 'lb',
            'lg' => 'lg',
            'li' => 'li',
            'ln' => 'ln',
            'lo' => 'lo',
            'lt' => 'lt',
            'lu' => 'lu',
            'lv' => 'lv',
            'mg' => 'mg',
            'mh' => 'mh',
            'mi' => 'mi',
            'mk' => 'mk',
            'ml' => 'ml',
            'mn' => 'mn',
            'mr' => 'mr',
            'ms' => 'ms',
            'mt' => 'mt',
            'my' => 'my',
            'na' => 'na',
            'nb' => 'nb',
            'nd' => 'nd',
            'ne' => 'ne',
            'ng' => 'ng',
            'nl' => 'nl',
            'nn' => 'nn',
            'no' => 'no',
            'nr' => 'nr',
            'nv' => 'nv',
            'ny' => 'ny',
            'oc' => 'oc',
            'oj' => 'oj',
            'om' => 'om',
            'or' => 'or',
            'os' => 'os',
            'pa' => 'pa',
            'pi' => 'pi',
            'pl' => 'pl',
            'ps' => 'ps',
            'pt_BR' => 'pt',
            'pt_PT' => 'pt',
            'qu' => 'qu',
            'rm' => 'rm',
            'rn' => 'rn',
            'ro' => 'ro',
            'ru' => 'ru',
            'rw' => 'rw',
            'sa' => 'sa',
            'sc' => 'sc',
            'sd' => 'sd',
            'se' => 'se',
            'sg' => 'sg',
            'si' => 'si',
            'sk' => 'sk',
            'sl' => 'sl',
            'sm' => 'sm',
            'sn' => 'sn',
            'so' => 'so',
            'sq' => 'sq',
            'sr@cyrillic' => 'sr',
            'sr@latin' => 'sr',
            'ss' => 'ss',
            'st' => 'st',
            'su' => 'su',
            'sv' => 'sv',
            'sw' => 'sw',
            'ta' => 'ta',
            'te' => 'te',
            'tg' => 'tg',
            'th' => 'th',
            'ti' => 'ti',
            'tk' => 'tk',
            'tl' => 'tl',
            'tn' => 'tn',
            'to' => 'to',
            'tr' => 'tr',
            'ts' => 'ts',
            'tt' => 'tt',
            'tw' => 'tw',
            'ty' => 'ty',
            'ug' => 'ug',
            'uk' => 'uk',
            'ur' => 'ur',
            'uz@cyrillic' => 'uz',
            'uz@latin' => 'uz',
            've' => 've',
            'vi' => 'vi',
            'vo' => 'vo',
            'wa' => 'wa',
            'wo' => 'wo',
            'xh' => 'xh',
            'yi' => 'yi',
            'yo' => 'yo',
            'za' => 'za',
            'zh_CN' => 'zh',
            'zh_Hant' => 'zh',
            'zh' => 'zh',
            'zu' => 'zu'
        ];
    }
}
