namespace Wa72\HtmlPageDom;

/**
 * Fungsi bantu statis untuk HtmlPageDom
 *
 * @package Wa72\HtmlPageDom
 */
class Pembantu {

    /**
     * menghapus baris baru dari string dan meminimalkan spasi (beberapa karakter spasi diganti dengan satu spasi)
     * berguna untuk membersihkan teks yang diambil oleh HtmlPageCrawler::text() (nodeValue dari DOMNode)
     *
     * @param string $string
     * @return string
     */
    public static function bersihkanBarisBaru($string)
    {
        $string = str_replace("\n", ' ', $string);
        $string = str_replace("\r", ' ', $string);
        $string = preg_replace('/\s+/', ' ', $string);
        return trim($string);
    }

    /**
     * Mengonversi string CSS menjadi array
     *
     * @param string $css daftar properti CSS yang dipisahkan oleh ;
     * @return array pasangan nama=>nilai dari properti CSS
     */
    public static function cssStringKeArray($css)
    {
        $pernyataan = explode(';', preg_replace('/\s+/s', ' ', $css));
        $gaya = array();
        foreach ($pernyataan sebagai $p) {
            $p = trim($p);
            if ('' === $p) {
                continue;
            }
            $pos = strpos($p, ':');
            if ($pos <= 0) {
                continue;
            } // pernyataan tidak valid, abaikan saja
            $kunci = trim(substr($p, 0, $pos));
            $nilai = trim(substr($p, $pos + 1));
            $gaya[$kunci] = $nilai;
        }
        return $gaya;
    }

    /**
     * Mengonversi array nama->nilai CSS menjadi string
     *
     * @param array $array pasangan nama=>nilai dari properti CSS
     * @return string daftar properti CSS yang dipisahkan oleh ;
     */
    public static function cssArrayKeString($array)
    {
        $gaya = '';
        foreach ($array sebagai $kunci => $nilai) {
            $gaya .= $kunci . ': ' . $nilai . ';';
        }
        return $gaya;
    }

    /**
     * Fungsi bantu untuk mendapatkan elemen body
     * dari sebuah fragmen HTML
     *
     * @param string $html Sebuah fragmen kode HTML
     * @param string $charset
     * @return \DOMNode Node body yang berisi node anak yang dibuat dari fragmen HTML
     */
    public static function getBodyNodeDariFragmenHtml($html, $charset = 'UTF-8')
    {

        $html = '<html><body>' . $html . '</body></html>';
        $d = self::muatHtml($html, $charset);
        return $d->getElementsByTagName('body')->item(0);
    }

    public static function muatHtml(string $html, $charset = 'UTF-8'): \DOMDocument
    {
        return self::parseXhtml($html, $charset);
    }
    /**
     * Fungsi awalnya diambil dari Symfony\Component\DomCrawler\Crawler
     * (c) Fabien Potencier <fabien@symfony.com>
     * Lisensi: MIT
     */
    private static function parseXhtml(string $htmlContent, string $charset = 'UTF-8'): \DOMDocument
    {
        $htmlContent = self::convertToHtmlEntities($htmlContent, $charset);

        $internalErrors = libxml_use_internal_errors(true);

        $dom = new \DOMDocument('1.0', $charset);
        $dom->validateOnParse = true;

        if ('' !== trim($htmlContent)) {
            // Metode PHP DOMDocument->loadHTML cenderung "memakan" tag penutup dalam string html di dalam elemen script
            // Opsi LIBXML_SCHEMA_CREATE tampaknya mencegah ini
            // lihat https://stackoverflow.com/questions/24575136/domdocument-removes-html-tags-in-javascript-string
            @$dom->loadHTML($htmlContent, \LIBXML_SCHEMA_CREATE);
        }

        libxml_use_internal_errors($internalErrors);

        return $dom;
    }

    /**
     * Mengonversi charset ke entitas HTML untuk memastikan parsing yang valid.
     * Fungsi diambil dari Symfony\Component\DomCrawler\Crawler
     * (c) Fabien Potencier <fabien@symfony.com>
     * Lisensi: MIT
     */
    private static function convertToHtmlEntities(string $htmlContent, string $charset = 'UTF-8'): string
    {
        set_error_handler(function () { throw new \Exception(); });

        try {
            return mb_encode_numericentity($htmlContent, [0x80, 0x10FFFF, 0, 0x1FFFFF], $charset);
        } catch (\Exception|\ValueError) {
            try {
                $htmlContent = iconv($charset, 'UTF-8', $htmlContent);
                $htmlContent = mb_encode_numericentity($htmlContent, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');
            } catch (\Exception|\ValueError) {
            }
            return $htmlContent;
        } finally {
            restore_error_handler();
        }
    }
}
