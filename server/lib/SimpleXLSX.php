<?php
/**
 * Class SimpleXLSX
 *
 * @author    shuchkin
 * @copyright 2017-2024
 * @license   https://github.com/shuchkin/simplexlsx/blob/master/license.md
 */

class SimpleXLSX
{
    // Excel formats
    const EXCEL_2007 = 'Excel2007';
    const EXCEL_5 = 'Excel5';
    const ODS = 'OOCalc';

    public static $CF = [ // Cell formats
        0  => 'General',
        1  => '0',
        2  => '0.00',
        3  => '#,##0',
        4  => '#,##0.00',
        9  => '0%',
        10 => '0.00%',
        11 => '0.00E+00',
        12 => '# ?/?',
        13 => '# ??/??',
        14 => 'mm-dd-yy',
        15 => 'd-mmm-yy',
        16 => 'd-mmm',
        17 => 'mmm-yy',
        18 => 'h:mm AM/PM',
        19 => 'h:mm:ss AM/PM',
        20 => 'h:mm',
        21 => 'h:mm:ss',
        22 => 'm/d/yy h:mm',

        37 => '#,##0 ;(#,##0)',
        38 => '#,##0 ;[Red](#,##0)',
        39 => '#,##0.00;(#,##0.00)',
        40 => '#,##0.00;[Red](#,##0.00)',

        45 => 'mm:ss',
        46 => '[h]:mm:ss',
        47 => 'mmss.0',
        48 => '##0.0E+0',
        49 => '@',

        // CHT
        27 => '[$-404]e/m/d',
        30 => 'm/d/yy',
        36 => '[$-404]e/m/d',
        50 => '[$-404]e/m/d',
        57 => '[$-404]e/m/d',

        // THA
        59 => 't0',
        60 => 't0.00',
        61 => 't#,##0',
        62 => 't#,##0.00',
        67 => 't0%',
        68 => 't0.00%',
        69 => 't#,##0',
        70 => 't#,##0.00',
    ];
    public $cellFormats = [];
    public $datetimeFormat = 'Y-m-d H:i:s';
    public $debug;

    /* @var SimpleXMLElement[] $sheets */
    private $sheets;
    private $sheetNames = [];
    private $sheetFiles = [];
    // scheme
    private $styles;
    private $hyperlinks;
    /* @var array[] $package */
    private $package;
    private $sharedstrings;
    private $date_formats = [];
    private $time_formats = [];

    private $wrong_date_formats = [
        'mm-dd-yy',
        'd-mmm-yy',
        'd-mmm',
        'mmm-yy',
        'h:mm AM/PM',
        'h:mm:ss AM/PM',
        'h:mm',
        'h:mm:ss',
        'm/d/yy h:mm',
        'mm:ss',
        '[h]:mm:ss',
        'mmss.0'
    ];

    // Don't remove this string! It's used for determining build date.
    public static $version = '0.10.2';

    public function __construct($filename = null, $is_data = null, $debug = null)
    {
        if ($debug) {
            $this->debug = true;
        }
        $this->cellFormats = self::$CF;
        $this->time_formats = [
            18, 19, 20, 21, 45, 46
        ];
        $this->date_formats = [
            14, 15, 16, 17, 22, 27, 30, 36, 50, 57
        ];
        if ($filename) {
            $this->parse($filename, $is_data);
        }
    }

    public static function parseFile($filename, $debug = false)
    {
        return self::parse($filename, false, $debug);
    }

    public static function parseData($data, $debug = false)
    {
        return self::parse($data, true, $debug);
    }

    public static function parse($filename, $is_data = false, $debug = false)
    {
        $xlsx = new self(null, false, $debug);
        $xlsx->parse($filename, $is_data);
        if ($xlsx->success()) {
            return $xlsx;
        }

        self::parseError($xlsx->error());
        self::parseErrno($xlsx->errno());

        return false;
    }

    private static $errno = 0;
    private static $error = '';

    public static function parseError($set = false)
    {
        if ($set) {
            self::$error = $set;
            if (self::$error) {
                error_log('SimpleXLSX: ' . self::$error);
            }
        }
        return self::$error;
    }
    public static function parseErrno($set = false)
    {
        if ($set) {
            self::$errno = (int) $set;
        }
        return self::$errno;
    }

    public function success()
    {
        return !self::$error;
    }
    public function errno() {
        return self::$errno;
    }
    public function error()
    {
        return self::$error;
    }

    public function parse($filename, $is_data = false)
    {
        self::parseError(false);
        self::parseErrno(false);

        if ($is_data) {
            $this->package = $this->extract($filename, true);
        } else {
            if (!is_readable($filename)) {
                self::parseError('File not found ' . $filename);
                self::parseErrno(1);
                return false;
            }
            $this->package = $this->extract($filename, false);
        }
        if ($this->package) {
            if ($this->package['error']) {
                self::parseError($this->package['error']);
                self::parseErrno($this->package['errno']);
                return false;
            }
            $this->styles = $this->getEntryXML('_rels/.rels');
            if ($this->styles && $this->styles->Relationship) {
                foreach ($this->styles->Relationship as $rel) {
                    $atts = $rel->attributes();
                    if ($atts['Type'] === 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles') {
                        $this->styles = $this->getEntryXML( (string) $atts['Target'] );
                        break;
                    }
                }
            }

            if ($this->styles->numFmts && $this->styles->numFmts->numFmt) {
                foreach ($this->styles->numFmts->numFmt as $v) {
                    $atts = $v->attributes();
                    $this->cellFormats[(int)$atts['numFmtId']] = (string)$atts['formatCode'];
                }
            }
            if ($this->styles->cellXfs && $this->styles->cellXfs->xf) {
                foreach ($this->styles->cellXfs->xf as $v) {
                    $atts = $v->attributes();
                    if (isset($atts['numFmtId'])) {
                        $this->cellFormats[] = (int)$atts['numFmtId'];
                    } else {
                        $this->cellFormats[] = 0;
                    }
                }
            }

            $this->sharedstrings = $this->getEntryXML('xl/sharedStrings.xml');

            $this->sheets = [];
            $workbook = $this->getEntryXML('xl/workbook.xml');
            if ($workbook->sheets) {
                foreach ($workbook->sheets->sheet as $s) {
                    $this->sheetNames[] = (string)$s['name'];
                }
            }
            $rels = $this->getEntryXML('xl/_rels/workbook.xml.rels');
            if ($rels) {
                foreach ($rels->Relationship as $rel) {
                    $atts = $rel->attributes();
                    if ($atts['Type'] === 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet') {
                        $this->sheetFiles[(string)$atts['Id']] = (string)$atts['Target'];
                    } elseif ($atts['Type'] === 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings') {
                        $this->sharedstrings = $this->getEntryXML( 'xl/'.(string) $atts['Target'] );
                    }
                }
            }

            return true;
        }
        self::parseError('Zip-archive corrupt');
        self::parseErrno(2);
        return false;
    }

    public function getSheetNames($all = false)
    {
        return $all ? $this->sheetFiles : $this->sheetNames;
    }

    public function getSheetName($idx)
    {
        return $this->sheetNames[$idx] ?? false;
    }

    public function getSheetIndex($name)
    {
        $i = array_search($name, $this->sheetNames, true);
        return $i !== false ? $i : false;
    }

    public function sheetsCount()
    {
        return count($this->sheetNames);
    }

    public function sheetName($idx)
    {
        return $this->sheetNames[$idx] ?? null;
    }

    public function worksheet($idx)
    {
        $s_idx = ($idx === null) ? 0 : $idx;
        if (isset($this->sheetNames[$s_idx])) {
            $name = $this->sheetNames[$s_idx];
            $id = array_search($name, $this->sheetNames, true);
            $rId = array_keys($this->sheetFiles)[$id];
            return $this->getEntryXML('xl/' . $this->sheetFiles[$rId]);
        }
        self::parseError('Sheet ' . $idx . ' not found');
        self::parseErrno(3);
        return false;
    }

    public function worksheetRaw($idx)
    {
        $s_idx = ($idx === null) ? 0 : $idx;
        if (isset($this->sheetNames[$s_idx])) {
            $name = $this->sheetNames[$s_idx];
            $id = array_search($name, $this->sheetNames, true);
            $rId = array_keys($this->sheetFiles)[$id];
            return $this->getEntryData('xl/' . $this->sheetFiles[$rId]);
        }
        self::parseError('Sheet ' . $idx . ' not found');
        self::parseErrno(3);
        return false;
    }

    public function getSheetData($idx)
    {
        return $this->worksheet($idx);
    }

    /**
     * @param int $idx
     *
     * @return SimpleXMLElement[]
     */
    public function sheets()
    {
        if ($this->sheets === null) {
            $this->sheets = [];
            foreach ($this->sheetNames as $id => $name) {
                $this->sheets[$id] = $this->worksheet($id);
            }
        }
        return $this->sheets;
    }

    /**
     * @param $idx
     *
     * @return array|bool
     */
    public function readRows($idx)
    {
        if (($ws = $this->worksheet($idx)) === false) {
            return false;
        }
        $rows = [];
        $curR = 0;
        $curC = 0;
        if ($ws->dimension) {
            $d = $ws->dimension->attributes();
            $this->max_row = (int)max(1, preg_replace('/[A-Z]/', '', $d['ref']));
        } else {
            $this->max_row = 0;
        }

        foreach ($ws->sheetData->row as $row) {
            $rows[] = $this->processingRow($row);
        }
        return $rows;
    }

    /**
     * @param int $idx
     * @param callable|null $callback
     *
     * @return \Generator|bool
     */
    public function readRowsEx($idx, $callback = null)
    {
        if (($ws = $this->worksheet($idx)) === false) {
            return false;
        }
        $rows = [];
        $curR = 0;
        $curC = 0;
        if ($ws->dimension) {
            $d = $ws->dimension->attributes();
            $this->max_row = (int)max(1, preg_replace('/[A-Z]/', '', $d['ref']));
        } else {
            $this->max_row = 0;
        }

        foreach ($ws->sheetData->row as $row) {
            yield $this->processingRow($row, $callback);
        }
    }

    /**
     * @param $sheet_idx
     *
     * @return array|bool
     */
    public function rows($sheet_idx = null)
    {
        return $this->readRows($sheet_idx);
    }

    /**
     * @param $sheet_idx
     * @param $callback
     *
     * @return \Generator|bool
     */
    public function rowsEx($sheet_idx = null, $callback = null)
    {
        return $this->readRowsEx($sheet_idx, $callback);
    }

    /**
     * @param $sheet_idx
     *
     * @return array|bool
     */
    public function toHTML($sheet_idx = null)
    {
        $sheet_idx = $sheet_idx === null ? 0 : $sheet_idx;

        if (($ws = $this->worksheet($sheet_idx)) === false) {
            return false;
        }
        $html = '<table class="simplexlsx">';
        $rows = $this->rows($sheet_idx);
        foreach ($rows as $r) {
            $html .= '<tr>';
            foreach ($r as $c) {
                $html .= '<td>' . $c . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }

    public function getHyperlinks()
    {
        return $this->hyperlinks;
    }

    public function getEntryData($name)
    {
        return $this->package['data'][$name] ?? false;
    }

    public function getEntryXML($name)
    {
        $data = $this.getEntryData($name);
        if ($data) {
            $data = preg_replace('/<(?!\?xml)/', '<', $data);
            // dirty remove all namespaces
            $data = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $data); // remove all xmlns attributes
            $data = preg_replace('/<(\/)?\w+:/', '<$1', $data); // remove all namespace prefixes
            return simplexml_load_string($data);
        }
        return false;
    }

    public function getStyle($c)
    {
        $atts = $c->attributes();
        return isset($atts['s']) ? (int)$atts['s'] : null;
    }

    public function getValue($c, $s = null)
    {
        // $s is style index
        if ($s === null) {
            $s = $this->getStyle($c);
        }
        $atts = $c->attributes();
        $v = '';
        if (isset($atts['t'])) {
            switch ((string)$atts['t']) {
                case 's': // shared string
                    if ((string)$c->v !== '') {
                        $si = $this->sharedstrings->si[(int)$c->v];
                        if (isset($si->t)) {
                            $v = (string)$si->t;
                        } elseif (isset($si->r)) {
                            foreach ($si->r as $r) {
                                $v .= (string)$r->t;
                            }
                        }
                    }
                    break;
                case 'b': // boolean
                    $v = (string)$c->v;
                    if ($v === '0') {
                        $v = false;
                    } elseif ($v === '1') {
                        $v = true;
                    } else {
                        $v = (bool)$c->v;
                    }
                    break;
                case 'i': // inline string
                    $v = (string)$c->v;
                    break;
                case 'e': // error
                    $v = (string)$c->v;
                    break;
                default:
                    $v = (string)$c->v;
            }
        } elseif ($c->v) {
            $v = (string)$c->v;
        }

        if (is_string($v) && $v !== '' && $s > 0) {
            $v = $this->format($v, $s);
        }

        return $v;
    }

    public function format($v, $s)
    {
        if (!is_numeric($v)) {
            return $v;
        }
        $is_time = false;
        $is_date = false;

        if (isset($this->cellFormats[$s])) {
            $format = $this->cellFormats[$s];
        } else {
            return $v;
        }
        if (is_int($format)) {
            if (isset($this->cellFormats[$format])) {
                $format = $this->cellFormats[$format];
            } else {
                return $v;
            }
        }
        // custom format
        if (strpos($format, 'm') !== false) {
            $is_date = true;
        }
        if (strpos($format, 'h') !== false) {
            $is_time = true;
        }

        if ($is_date || $is_time) {
            return $this->datetime($v, $is_date, $is_time);
        }

        // is it a built-in format?
        $s = (int) $s;
        if (in_array($s, $this->date_formats, true)) {
            return $this->datetime($v, true, false);
        }
        if (in_array($s, $this->time_formats, true)) {
            return $this->datetime($v, false, true);
        }
        if ($s === 49) { // text
            return (string) $v;
        }

        return $v;
    }

    public function datetime($v, $is_date, $is_time)
    {
        $v = (float)$v;
        // https://stackoverflow.com/questions/13346323/how-to-recognize-and-convert-excel-dates-into-unix-timestamps-in-php
        // https://stackoverflow.com/questions/10339534/php-excel-reader-and-date-format
        // https://www.day-calculator.com/tasks/excel-date-to-regular-date.html
        // Bug: 1900-02-29 is not a leap year
        // Misinterpretation: 1900-01-00 is not a date
        $v_abs = abs($v);
        if ($v_abs >= 1) {
            $d = (int)$v;
            $t = $v - $d;
            // bug in excel 1900 is a leap year
            if ($d > 60) {
                $d--;
            }
        } else {
            $d = 0;
            $t = $v;
        }

        if ($is_date) {
            // 25569 is 1970-01-01
            $d_ts = ($d - 25569) * 86400;
            if ($is_time) {
                $t_ts = round($t * 86400);
                return gmdate($this->datetimeFormat, $d_ts + $t_ts);
            }
            return gmdate(explode(' ', $this->datetimeFormat)[0], $d_ts);
        }

        // time only
        $t_ts = round($t * 86400);
        return gmdate(explode(' ', $this->datetimeFormat)[1], $t_ts);
    }

    public function _get_simple_xml_value($c)
    {
        return (string)$c->v;
    }

    public static function getCol(SimpleXMLElement $row, $c_idx)
    {
        return $row->c[$c_idx] ?? null;
    }

    public static function getRows(SimpleXMLElement $ws)
    {
        return $ws->sheetData->row;
    }

    public static function getCell(SimpleXMLElement $row, $c_idx)
    {
        return $row->c[$c_idx] ?? null;
    }

    public static function getCellValue(SimpleXMLElement $c)
    {
        return (string)$c->v;
    }

    private function processingRow($row, $callback = null)
    {
        $r_atts = $row->attributes();
        $r_idx = (int)$r_atts['r'];
        $cells = [];
        $c_idx = 0;
        foreach ($row->c as $c) {
            $c_atts = $c->attributes();
            $c_idx_s = (string)$c_atts['r'];
            $c_idx_n = (int)self::get_char_index($c_idx_s);

            if ($c_idx_n > $c_idx) {
                for ($i = $c_idx; $i < $c_idx_n; $i++) {
                    $cells[$i] = '';
                }
            }
            $c_idx = $c_idx_n;

            $val = $this->getValue($c);

            if ($callback) {
                $val = call_user_func($callback, $r_idx, $c_idx, $val);
            }
            $cells[$c_idx] = $val;
            $c_idx++;
        }
        return $cells;
    }

    private function extract($filename, $is_data)
    {
        $zip = new ZipArchive();
        $tmp_dir_name = '';
        $tmp_file_name = '';

        if ($is_data) {
            $tmp_dir_name = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'simplexlsx_' . uniqid();
            mkdir($tmp_dir_name);
            $tmp_file_name = $tmp_dir_name . DIRECTORY_SEPARATOR . 'data.zip';
            file_put_contents($tmp_file_name, $filename);
            $filename = $tmp_file_name;
        }

        $res = $zip->open($filename, ZIPARCHIVE::RDONLY);
        if ($res !== true) {
            if ($tmp_dir_name) {
                rmdir($tmp_dir_name);
            }
            return [
                'error' => 'failed to open zip archive',
                'errno' => 5,
                'data' => null
            ];
        }

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = $stat['name'];
            if ($name[strlen($name) - 1] === '/') {
                continue;
            }
            $entries[$name] = $zip->getFromIndex($i);
        }
        $zip->close();

        if ($tmp_file_name) {
            unlink($tmp_file_name);
        }
        if ($tmp_dir_name) {
            rmdir($tmp_dir_name);
        }

        return [
            'error' => false,
            'errno' => 0,
            'data' => $entries
        ];
    }

    public static function get_char_index($s)
    {
        $l = strlen($s);
        $i = 0;
        for ($p = 0; $p < $l; $p++) {
            $c = $s[$p];
            if ($c >= 'A' && $c <= 'Z') {
                $i = $i * 26 + (ord($c) - 64);
            } else {
                break;
            }
        }
        return $i - 1;
    }
}
