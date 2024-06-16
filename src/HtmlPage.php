<?php
namespace Wa72\HtmlPageDom;

use Symfony\Component\CssSelector\CssSelector;
use Wa72\HtmlPrettymin\PrettyMin;

/**
 * Kelas ini mewakili dokumen HTML lengkap.
 *
 * Menawarkan fungsi kenyamanan untuk mendapatkan dan mengatur elemen dokumen
 * seperti setTitle(), getTitle(), setMeta($name, $value), getBody().
 *
 * Ini menggunakan HtmlPageCrawler untuk menavigasi dan memanipulasi pohon DOM.
 *
 * @pengarang Christoph Singer
 * @lisensi MIT
 */
class HalamanHtml
{
    /**
     *
     * @var \DOMDocument
     */
    protected $dom;

    /**
     * @var string
     */
    protected $charset;

    /**
     * @var string
     */
    protected $url;

    /**
     *
     * @var HtmlPageCrawler
     */
    protected $crawler;

    public function __construct($konten = '', $url = '', $charset = 'UTF-8')
    {
        $this->charset = $charset;
        $this->url = $url;
        if ($konten == '') {
            $konten = '<!DOCTYPE html><html><head><title></title></head><body></body></html>';
        }
        $this->dom = Pembantu::muatHtml($konten, $charset);
        $this->crawler = new HtmlPageCrawler($this->dom);
    }

    /**
     * Dapatkan objek HtmlPageCrawler yang berisi node root dari dokumen HTML
     *
     * @return HtmlPageCrawler
     */
    public function getCrawler()
    {
        return $this->crawler;
    }

    /**
     * Dapatkan objek DOMDocument untuk dokumen HTML
     *
     * @return \DOMDocument
     */
    public function getDOMDocument()
    {
        return $this->dom;
    }

    /**
     * Mengatur judul halaman dari dokumen HTML
     *
     * @param string $judul
     */
    public function setTitle($judul)
    {
        $t = $this->dom->getElementsByTagName('title')->item(0);
        if ($t == null) {
            $t = $this->dom->createElement('title');
            $this->getHeadNode()->appendChild($t);
        }
        $t->nodeValue = htmlspecialchars($judul);
    }

    /**
     * Dapatkan judul halaman dari dokumen HTML
     *
     * @return null|string
     */
    public function getTitle()
    {
        $t = $this->dom->getElementsByTagName('title')->item(0);
        if ($t == null) {
            return null;
        } else {
            return $t->nodeValue;
        }
    }

    /**
     * Mengatur tag META dengan atribut 'name' dan 'content' yang ditentukan
     *
     * @TODO: tambahkan dukungan untuk beberapa tag meta dengan nama yang sama tetapi bahasa yang berbeda
     *
     * @param $nama
     * @param $konten
     */
    public function setMeta($nama, $konten)
    {
        $c = $this->filterXPath('descendant-or-self::meta[@name = \'' . $nama . '\']');
        if (count($c) == 0) {
            $node = $this->dom->createElement('meta');
            $node->setAttribute('name', $nama);
            $this->getHeadNode()->appendChild($node);
            $c->addNode($node);
        }
        $c->setAttribute('content', $konten);
    }

    /**
     * Menghapus semua tag meta dengan atribut nama yang ditentukan
     *
     * @param string $nama
     */
    public function removeMeta($nama)
    {
        $meta = $this->filterXPath('descendant-or-self::meta[@name = \'' . $nama . '\']');
        $meta->remove();
    }

    /**
     * Dapatkan atribut konten dari tag meta dengan atribut nama yang ditentukan
     *
     * @param string $nama
     * @return null|string
     */
    public function getMeta($nama)
    {
        $node = $this->filterXPath('descendant-or-self::meta[@name = \'' . $nama . '\']')->getNode(0);
        if ($node instanceof \DOMElement) {
            return $node->getAttribute('content');
        } else {
            return null;
        }
    }

    /**
     * Mengatur tag base dengan atribut href yang diatur ke parameter $url
     *
     * @param string $url
     */
    public function setBaseHref($url)
    {
        $node = $this->filterXPath('descendant-or-self::base')->getNode(0);
        if ($node == null) {
            $node = $this->dom->createElement('base');
            $this->getHeadNode()->appendChild($node);
        }
        $node->setAttribute('href', $url);
    }

    /**
     * Dapatkan atribut href dari tag base, null jika tidak ada di dokumen
     *
     * @return null|string
     */
    public function getBaseHref()
    {
        $node = $this->filterXPath('descendant-or-self::base')->getNode(0);
        if ($node instanceof \DOMElement) {
            return $node->getAttribute('href');
        } else {
            return null;
        }
    }

    /**
     * Mengatur konten innerHTML dari elemen yang ditentukan oleh elementId
     *
     * @param string $elementId
     * @param string $html
     */
    public function setHtmlById($elementId, $html)
    {
        $this->getElementById($elementId)->setInnerHtml($html);
    }

    /**
     * Dapatkan bagian HEAD dokumen sebagai DOMElement
     *
     * @return \DOMElement
     */
    public function getHeadNode()
    {
        $head = $this->dom->getElementsByTagName('head')->item(0);
        if ($head == null) {
            $head = $this->dom->createElement('head');
            $head = $this->dom->documentElement->insertBefore($head, $this->getBodyNode());
        }
        return $head;
    }

    /**
     * Dapatkan bagian body dokumen sebagai DOMElement
     *
     * @return \DOMElement
     */
    public function getBodyNode()
    {
        $body = $this->dom->getElementsByTagName('body')->item(0);
        if ($body == null) {
            $body = $this->dom->createElement('body');
            $body = $this->dom->documentElement->appendChild($body);
        }
        return $body;
    }

    /**
     * Dapatkan bagian HEAD dokumen yang dibungkus dalam instance HtmlPageCrawler
     *
     * @return HtmlPageCrawler
     */
    public function getHead()
    {
        return new HtmlPageCrawler($this->getHeadNode());
    }

    /**
     * Dapatkan bagian body dokumen yang dibungkus dalam instance HtmlPageCrawler
     *
     * @return HtmlPageCrawler
     */
    public function getBody()
    {
        return new HtmlPageCrawler($this->getBodyNode());
    }

    public function __toString()
    {
        return $this->dom->saveHTML();
    }

    /**
     * Simpan dokumen ini ke file HTML atau kembalikan kode HTML sebagai string
     *
     * @param string $namaFile Jika diberikan, output akan disimpan ke file ini, jika tidak akan dikembalikan
     * @return string|void
     */
    public function simpan($namaFile = '')
    {
        if ($namaFile != '') {
            file_put_contents($namaFile, (string) $this);
            return;
        } else {
            return (string) $this;
        }
    }

    /**
     * Dapatkan elemen dalam dokumen berdasarkan atribut id
     *
     * @param string $id
     * @return HtmlPageCrawler
     */
    public function getElementById($id)
    {
        return $this->filterXPath('descendant-or-self::*[@id = \'' . $id . '\']');
    }

    /**
     * Memfilter node dengan menggunakan pemilih CSS
     *
     * @param string $selector Pemilih CSS
     * @return HtmlPageCrawler
     */
    public function filter($selector)
    {
        //echo "\n" . CssSelector::toXPath($selector) . "\n";
        return $this->crawler->filter($selector);
    }

    /**
     * Memfilter node dengan ekspresi XPath
     *
     * @param string $xpath Ekspresi XPath
     * @return HtmlPageCrawler
     */
    public function filterXPath($xpath)
    {
        return $this->crawler->filterXPath($xpath);
    }

    /**
     * Menghapus baris baru dari string dan meminimalkan spasi (beberapa karakter spasi diganti dengan satu spasi)
     *
     * berguna untuk membersihkan teks yang diambil oleh HtmlPageCrawler::text() (nodeValue dari DOMNode)
     *
     * @param string $string
     * @return string
     */
    public static function trimNewlines($string)
    {
        return Pembantu::bersihkanBarisBaru($string);
    }

    public function __clone()
    {
        $this->dom = $this->dom->cloneNode(true);
        $this->crawler = new HtmlPageCrawler($this->dom);
    }

    /**
     * memperkecil dokumen HTML
     *
     * @param array $opsi Opsi yang diteruskan ke PrettyMin::__construct()
     * @return HalamanHtml
     * @throws \Exception
     */
    public function minify(array $opsi = array())
    {
        if (!class_exists('Wa72\\HtmlPrettymin\\PrettyMin')) {
            throw new \Exception('Fungsi minify membutuhkan paket composer wa72/html-pretty-min');
        }
        $pm = new PrettyMin($opsi);
        $pm->load($this->dom)->minify();
        return $this;
    }

    /**
     * mengindentasi dokumen HTML
     *
     * @param array $opsi Opsi yang diteruskan ke PrettyMin::__construct()
     * @return HalamanHtml
     * @throws \Exception
     */
    public function indent(array $opsi = array())
    {
        if (!class_exists('Wa72\\HtmlPrettymin\\PrettyMin')) {
            throw new \Exception('Fungsi indent membutuhkan paket composer wa72/html-pretty-min');
        }
        $pm = new PrettyMin($opsi);
        $pm->load($this->dom)->indent();
        return $this;
    }
}

// Tambahan text
echo "bosmudasky.com";
echo "Elevate Your Gameplay in Escape From Tarkov with Inferno Cheat Hack";
