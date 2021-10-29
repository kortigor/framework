<?php

declare(strict_types=1);

namespace utils\stemmer;

/**
 * Стеммер Портера
 * Извлечение корня из русского слова, без словаря.
 * 
 * Стеммер Портера — алгоритм стемминга, опубликованный Мартином Портером в 1980 году.
 * Оригинальная версия стеммера была предназначена для английского языка.
 * Впоследствии Мартин создал проект "Snowball" и, используя основную идею алгоритма,
 * написал стеммеры для распространённых индоевропейских языков, в том числе для русского.
 * 
 * Алгоритм не использует морфологический словарь, а только применяя последовательно ряд правил,
 * отсекает окончания и суффиксы, основываясь на особенностях языка,
 * в связи с чем работает быстро, но не всегда безошибочно.
 * 
 * Примеры:
 * ```
 * $stemmer = new LinguaStemRu();
 * echo $stemmer->stemWord('Автомобиль');
 * echo $stemmer->stemWord('Автомобилем');
 * echo $stemmer->stemWord('Автомобиля');
 * ```
 * В результате получим три раза слово `автомобил`.
 * 
 * ```
 * $stemmer = new LinguaStemRu();
 * echo $stemmer->stemText('Любовь к Родине – это очень сильное чувство.');
 * ```
 * Получим:
 * `любов к родин – это очен сильн чувство`.
 */
class LinguaStemRu
{
    const VERSION = '0.02';
    const VOWEL = '/аеиоуыэюя/';
    const PERFECTIVEGROUND = '/((ив|ивши|ившись|ыв|ывши|ывшись)|((?<=[ая])(в|вши|вшись)))$/';
    const REFLEXIVE = '/(с[яь])$/';
    const ADJECTIVE = '/(ее|ие|ые|ое|ими|ыми|ей|ий|ый|ой|ем|им|ым|ом|его|ого|еых|ую|юю|ая|яя|ою|ею)$/';
    const PARTICIPLE = '/((ивш|ывш|ующ)|((?<=[ая])(ем|нн|вш|ющ|щ)))$/';
    const VERB = '/((ила|ыла|ена|ейте|уйте|ите|или|ыли|ей|уй|ил|ыл|им|ым|ены|ить|ыть|ишь|ую|ю)|((?<=[ая])(ла|на|ете|йте|ли|й|л|ем|н|ло|но|ет|ют|ны|ть|ешь|нно)))$/';
    const NOUN = '/(а|ев|ов|ие|ье|е|иями|ями|ами|еи|ии|и|ией|ей|ой|ий|й|и|ы|ь|ию|ью|ю|ия|ья|я)$/';
    const RVRE = '/^(.*?[аеиоуыэюя])(.*)$/';
    const DERIVATIONAL = '/[^аеиоуыэюя][аеиоуыэюя]+[^аеиоуыэюя]+[аеиоуыэюя].*(?<=о)сть?$/';

    protected int $stemCaching = 0;

    protected array $stemCache = [];

    /**
     * Стэмит слово
     * 
     * @param string $word
     * 
     * @return string
     */
    public function stemWord(string $word): string
    {
        $word = mb_strtolower($word);
        $word = str_replace('ё', 'е', $word); // замена ё на е, что бы учитывалась как одна и та же буква

        # Check against cache of stemmed words
        if ($this->stemCaching && isset($this->stemCache[$word])) {
            return $this->stemCache[$word];
        }

        $stem = $word;
        do {
            if (!preg_match(self::RVRE, $word, $p)) break;
            $start = $p[1];
            $RV = $p[2];
            if (!$RV) break;

            # Step 1
            if (!$this->s($RV, self::PERFECTIVEGROUND, '')) {
                $this->s($RV, self::REFLEXIVE, '');

                if ($this->s($RV, self::ADJECTIVE, '')) {
                    $this->s($RV, self::PARTICIPLE, '');
                } else {
                    if (!$this->s($RV, self::VERB, ''))
                        $this->s($RV, self::NOUN, '');
                }
            }

            # Step 2
            $this->s($RV, '/и$/', '');

            # Step 3
            if ($this->m($RV, self::DERIVATIONAL))
                $this->s($RV, '/ость?$/', '');

            # Step 4
            if (!$this->s($RV, '/ь$/', '')) {
                $this->s($RV, '/ейше?/', '');
                $this->s($RV, '/нн$/', 'н');
            }

            $stem = $start . $RV;
        } while (false);

        if ($this->stemCaching) {
            $this->stemCache[$word] = $stem;
        }

        return $stem;
    }

    /**
     * Стэмит все русские слова в тексте, оставляя пробелы и прочие знаки препинания на месте.
     * 
     * @param string $text
     * @return string
     */
    public function stemText(string $text): string
    {
        $separators_arr = ['?', ' ', '.', ',', ';', '!', '"', '\'', '`', "\r", "\n", "\t"];
        $pos = 0;
        while ($pos < mb_strlen($text)) {
            $min_new_pos = mb_strlen($text);
            foreach ($separators_arr as $sep) {
                $newpos_candidate = mb_strpos($text, $sep, $pos);
                if ($newpos_candidate !== false) {
                    $min_new_pos = ($newpos_candidate < $min_new_pos) ? $newpos_candidate : $min_new_pos;
                }
            }
            $newpos = $min_new_pos;
            $word_part = mb_substr($text, $pos, $newpos - $pos);
            $word = preg_replace("/[^АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдеёжзийклмнопрстуфхцчшщъыьэюя\x{2010}-]/u", "", $word_part);
            if ($word == '') {
                $pos = $newpos + 1;
            } else {
                $word_stemmed = $this->stemWord($word);
                $word_stemmed_part = str_replace($word, $word_stemmed, $word_part);

                $text = mb_substr($text, 0, $pos) . $word_stemmed_part . mb_substr($text, $newpos);

                $pos = $newpos - (mb_strlen($word) - mb_strlen($word_stemmed));
            }
        }

        return $text;
    }

    public function stemCaching($parm_ref): int
    {
        $caching_level = $parm_ref['-level'] ?? null;
        if ($caching_level) {
            if (!$this->m($caching_level, '/^[012]$/')) {
                throw new \Exception(__CLASS__ . "::stemCaching() - Valid values are '0','1', '2'. '{$caching_level}' is invalid");
            }
            $this->stemCaching = $caching_level;
        }

        return $this->stemCaching;
    }

    public function clearStemCache(): void
    {
        $this->stemCache = [];
    }

    private function s(&$s, $re, $to): bool
    {
        $orig = $s;
        $s = preg_replace($re, $to, $s);
        return $orig !== $s;
    }

    private function m($s, $re)
    {
        return preg_match($re, $s);
    }
}