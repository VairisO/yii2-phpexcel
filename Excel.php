<?php /** @noinspection PhpUndefinedClassInspection */

namespace moonland\phpexcel;

use DateTime;
use DateTimeZone;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Yii;
use yii\base\Model;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;
use yii\base\InvalidArgumentException;
use yii\helpers\VarDumper;
use yii\i18n\Formatter;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Excel Widget for generate Excel File or for load Excel File.
 *
 * Usage
 * -----
 *
 * Exporting data into an excel file.
 *
 * ~~~
 *
 * // export data only one worksheet.
 *
 * \moonland\phpexcel\Excel::widget([
 *        'models' => $allModels,
 *        'mode' => 'export', //default value as 'export'
 *        'columns' => ['column1','column2','column3'],
 *        //without header working, because the header will be get label from attribute label.
 *        'headers' => ['column1' => 'Header Column 1','column2' => 'Header Column 2', 'column3' => 'Header Column 3'],
 * ]);
 *
 * \moonland\phpexcel\Excel::export([
 *        'models' => $allModels,
 *        'columns' => ['column1','column2','column3'],
 *        //without header working, because the header will be get label from attribute label.
 *        'headers' => ['column1' => 'Header Column 1','column2' => 'Header Column 2', 'column3' => 'Header Column 3'],
 * ]);
 *
 * // export data with multiple worksheet.
 *
 * \moonland\phpexcel\Excel::widget([
 *        'isMultipleSheet' => true,
 *        'models' => [
 *            'sheet1' => $allModels1,
 *            'sheet2' => $allModels2,
 *            'sheet3' => $allModels3
 *        ],
 *        'mode' => 'export', //default value as 'export'
 *        'columns' => [
 *            'sheet1' => ['column1','column2','column3'],
 *            'sheet2' => ['column1','column2','column3'],
 *            'sheet3' => ['column1','column2','column3']
 *        ],
 *        //without header working, because the header will be get label from attribute label.
 *        'headers' => [
 *            'sheet1' => ['column1' => 'Header Column 1','column2' => 'Header Column 2', 'column3' => 'Header Column 3'],
 *            'sheet2' => ['column1' => 'Header Column 1','column2' => 'Header Column 2', 'column3' => 'Header Column 3'],
 *            'sheet3' => ['column1' => 'Header Column 1','column2' => 'Header Column 2', 'column3' => 'Header Column 3']
 *        ],
 * ]);
 *
 * \moonland\phpexcel\Excel::export([
 *        'isMultipleSheet' => true,
 *        'models' => [
 *            'sheet1' => $allModels1,
 *            'sheet2' => $allModels2,
 *            'sheet3' => $allModels3
 *        ],
 *        'columns' => [
 *            'sheet1' => ['column1','column2','column3'],
 *            'sheet2' => ['column1','column2','column3'],
 *            'sheet3' => ['column1','column2','column3']
 *        ],
 *        //without header working, because the header will be get label from attribute label.
 *        'headers' => [
 *            'sheet1' => ['column1' => 'Header Column 1','column2' => 'Header Column 2', 'column3' => 'Header Column 3'],
 *            'sheet2' => ['column1' => 'Header Column 1','column2' => 'Header Column 2', 'column3' => 'Header Column 3'],
 *            'sheet3' => ['column1' => 'Header Column 1','column2' => 'Header Column 2', 'column3' => 'Header Column 3']
 *        ],
 * ]);
 *
 * ~~~
 *
 * New Feature for exporting data, you can use this if you familiar yii gridview.
 * That is same with gridview data column.
 * Columns in array mode valid params are 'attribute', 'header', 'format', 'value', and footer (TODO).
 * Columns in string mode valid layout are 'attribute:format:header:footer(TODO)'.
 *
 * ~~~
 *
 * \moonland\phpexcel\Excel::export([
 *    'models' => Post::find()->all(),
 *        'columns' => [
 *            'author.name:text:Author Name',
 *            [
 *                    'attribute' => 'content',
 *                    'header' => 'Content Post',
 *                    'headerStyle' => [
 *                        'font' => [
 *                          'bold' => true,
 *                          'color' => array('rgb' => 'FFFDFE' )
 *                        ],
 *                     ],
 *                    'format' => 'text',
 *                    'autoSize' => true,
 *                    'value' => function($model) {
 *                        return ExampleClass::removeText('example', $model->content);
 *                    },
 *                    'visible' => true,
 *       			  'excelWidth' => 50,
 *                    'excelWrap' => true
 *            ],
 *            'like_it:text:Reader like this content',
 *            'created_at:datetime',
 *            [
 *                    'attribute' => 'updated_at',
 *                    'format' => 'date',
 *            ],
 *        ],
 *        'headers' => [
 *            'created_at' => 'Date Created Content',
 *        ],
 * ]);
 *
 * ~~~
 *
 *
 * Import file excel and return into an array.
 *
 * ~~~
 *
 * $data = \moonland\phpexcel\Excel::import($fileName, $config); // $config is an optional
 *
 * $data = \moonland\phpexcel\Excel::widget([
 *        'mode' => 'import',
 *        'fileName' => $fileName,
 *        'setFirstRecordAsKeys' => true, // if you want to set the keys of record column with first record, if it not set, the header with use the alphabet column on excel.
 *        'setIndexSheetByName' => true, // set this if your excel data with multiple worksheet, the index of array will be set with the sheet name. If this not set, the index will use numeric.
 *        'getOnlySheet' => 'sheet1', // you can set this property if you want to get the specified sheet from the excel data with multiple worksheet.
 * ]);
 *
 * $data = \moonland\phpexcel\Excel::import($fileName, [
 *        'setFirstRecordAsKeys' => true, // if you want to set the keys of record column with first record, if it not set, the header with use the alphabet column on excel.
 *        'setIndexSheetByName' => true, // set this if your excel data with multiple worksheet, the index of array will be set with the sheet name. If this not set, the index will use numeric.
 *        'getOnlySheet' => 'sheet1', // you can set this property if you want to get the specified sheet from the excel data with multiple worksheet.
 *    ]);
 *
 * // import data with multiple file.
 *
 * $data = \moonland\phpexcel\Excel::widget([
 *        'mode' => 'import',
 *        'fileName' => [
 *            'file1' => $fileName1,
 *            'file2' => $fileName2,
 *            'file3' => $fileName3,
 *        ],
 *        'setFirstRecordAsKeys' => true, // if you want to set the keys of record column with first record, if it not set, the header with use the alphabet column on excel.
 *        'setIndexSheetByName' => true, // set this if your excel data with multiple worksheet, the index of array will be set with the sheet name. If this not set, the index will use numeric.
 *        'getOnlySheet' => 'sheet1', // you can set this property if you want to get the specified sheet from the excel data with multiple worksheet.
 * ]);
 *
 * $data = \moonland\phpexcel\Excel::import([
 *            'file1' => $fileName1,
 *            'file2' => $fileName2,
 *            'file3' => $fileName3,
 *        ], [
 *        'setFirstRecordAsKeys' => true, // if you want to set the keys of record column with first record, if it not set, the header with use the alphabet column on excel.
 *        'setIndexSheetByName' => true, // set this if your excel data with multiple worksheet, the index of array will be set with the sheet name. If this not set, the index will use numeric.
 *        'getOnlySheet' => 'sheet1', // you can set this property if you want to get the specified sheet from the excel data with multiple worksheet.
 *    ]);
 *
 * ~~~
 *
 * Result example from the code on the top :
 *
 * ~~~
 *
 * // only one sheet or specified sheet.
 *
 * Array([0] => Array([name] => Anam, [email] => moh.khoirul.anaam@gmail.com, [framework interest] => Yii2),
 * [1] => Array([name] => Example, [email] => example@moonlandsoft.com, [framework interest] => Yii2));
 *
 * // data with multiple worksheet
 *
 * Array([Sheet1] => Array([0] => Array([name] => Anam, [email] => moh.khoirul.anaam@gmail.com, [framework interest] => Yii2),
 * [1] => Array([name] => Example, [email] => example@moonlandsoft.com, [framework interest] => Yii2)),
 * [Sheet2] => Array([0] => Array([name] => Anam, [email] => moh.khoirul.anaam@gmail.com, [framework interest] => Yii2),
 * [1] => Array([name] => Example, [email] => example@moonlandsoft.com, [framework interest] => Yii2)));
 *
 * // data with multiple file and specified sheet or only one worksheet
 *
 * Array([file1] => Array([0] => Array([name] => Anam, [email] => moh.khoirul.anaam@gmail.com, [framework interest] => Yii2),
 * [1] => Array([name] => Example, [email] => example@moonlandsoft.com, [framework interest] => Yii2)),
 * [file2] => Array([0] => Array([name] => Anam, [email] => moh.khoirul.anaam@gmail.com, [framework interest] => Yii2),
 * [1] => Array([name] => Example, [email] => example@moonlandsoft.com, [framework interest] => Yii2)));
 *
 * // data with multiple file and multiple worksheet
 *
 * Array([file1] => Array([Sheet1] => Array([0] => Array([name] => Anam, [email] => moh.khoirul.anaam@gmail.com, [framework interest] => Yii2),
 * [1] => Array([name] => Example, [email] => example@moonlandsoft.com, [framework interest] => Yii2)),
 * [Sheet2] => Array([0] => Array([name] => Anam, [email] => moh.khoirul.anaam@gmail.com, [framework interest] => Yii2),
 * [1] => Array([name] => Example, [email] => example@moonlandsoft.com, [framework interest] => Yii2))),
 * [file2] => Array([Sheet1] => Array([0] => Array([name] => Anam, [email] => moh.khoirul.anaam@gmail.com, [framework interest] => Yii2),
 * [1] => Array([name] => Example, [email] => example@moonlandsoft.com, [framework interest] => Yii2)),
 * [Sheet2] => Array([0] => Array([name] => Anam, [email] => moh.khoirul.anaam@gmail.com, [framework interest] => Yii2),
 * [1] => Array([name] => Example, [email] => example@moonlandsoft.com, [framework interest] => Yii2))));
 *
 * ~~~
 *
 * @property string $mode is an export mode or import mode. valid value are 'export' and 'import'
 * @property boolean $isMultipleSheet for set the export excel with multiple sheet.
 * @property array $properties for set property on the excel object.
 * @property array $models Model object or DataProvider object with much data.
 * @property array $columns to get the attributes from the model, this valid value only the exist attribute on the model.
 * If this is not set, then all attribute of the model will be set as columns.
 * @property array $headers to set the header column on first line. Set this if want to custom header.
 * If not set, the header will get attributes label of model attributes.
 * @property string|array $fileName is a name for file name to export or import. Multiple file name only use for import mode, not work if you use the export mode.
 * @property string $savePath is a directory to save the file or you can blank this to set the file as attachment.
 * @property string $format for excel to export. Valid value are 'Excel5','Excel2007','Excel2003XML','00Calc','Gnumeric'.
 * @property boolean $setFirstTitle to set the title column on the first line. The columns will have a header on the first line.
 * @property boolean $asAttachment to set the file excel to download mode.
 * @property boolean $setFirstRecordAsKeys to set the first record on excel file to a keys of array per line.
 * If you want to set the keys of record column with first record, if it not set, the header with use the alphabet column on excel.
 * @property boolean $setIndexSheetByName to set the sheet index by sheet name or array result if the sheet not only one
 * @property string $getOnlySheet is a sheet name to getting the data. This is only get the sheet with same name.
 * @property array|Formatter $formatter the formatter used to format model attribute values into displayable texts.
 * This can be either an instance of [[Formatter]] or an configuration array for creating the [[Formatter]]
 * instance. If this property is not set, the "formatter" application component will be used.
 *
 * @author Moh Khoirul Anam <moh.khoirul.anaam@gmail.com>
 * @copyright 2014
 * @since 1
 */
class Excel extends Widget
{
    /**
     * @var string mode is an export mode or import mode. valid value are 'export' and 'import'.
     */
    public $mode = self::EXPORT;
    /**
     * @var boolean for set the export excel with multiple sheet.
     */
    public $isMultipleSheet = false;
    /**
     *
     * @var array properties for set property on the excel object.
     */
    public $properties;
    /**
     * @var Model object or DataProvider object with much data.
     */
    public $models;
    /**
     * @var array columns to get the attributes from the model, this valid value only the exist attribute on the model.
     * If this is not set, then all attribute of the model will be set as columns.
     */
    public $columns = [];
    /**
     * @var array header to set the header column on first line. Set this if want to custom header.
     * If not set, the header will get attributes label of model attributes.
     */
    public $headers = [];
    /**
     * @var string|array name for file name to export or save.
     */
    public $fileName;
    /**
     * @var string save path is a directory to save the file or you can blank this to set the file as attachment.
     */
    public $savePath;
    /**
     * @var string format for excel to export. Valid value are 'Excel5','Excel2007','Excel2003XML','00Calc','Gnumeric'.
     */
    public $format;
    /**
     * @var boolean to set the title column on the first line.
     */
    public $setFirstTitle = true;
    /**
     * @var boolean to set the file excel to download mode.
     */
    public $asAttachment = true;
    /**
     * @var boolean to set the first record on excel file to a keys of array per line.
     * If you want to set the keys of record column with first record, if it not set, the header with use the alphabet column on excel.
     */
    public $setFirstRecordAsKeys = true;
    /**
     * @var boolean to set the sheet index by sheet name or array result if the sheet not only one.
     */
    public $setIndexSheetByName = false;
    /**
     * @var string sheetname to getting. This is only get the sheet with same name.
     */
    public $getOnlySheet;
    /**
     * @var boolean to set the import data will return as array.
     */
    public $asArray;
    /**
     * @var array to unread record by index number.
     */
    public $leaveRecordByIndex = [];
    /**
     * @var array to read record by index, other will leave.
     */
    public $getOnlyRecordByIndex = [];
    /**
     * @var array|Formatter the formatter used to format model attribute values into displayable texts.
     * This can be either an instance of [[Formatter]] or an configuration array for creating the [[Formatter]]
     * instance. If this property is not set, the "formatter" application component will be used.
     */
    public $formatter;

    public $decimalSeparator;

    public $thousandSeparator;

    /**
     * @var string format in excel style
     */
    public $dateFormat  = 'dd/mm/yy';
    public $dateTimeFormat  = 'd/m/yy h:mm';
    /**
     * @var bool freeze header rows
     */
    public $freezeHeader = true;

    public $autoFilter = true;
    public $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFDFE' ]
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_JUSTIFY
        ],
        'borders' => [
            'top' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => '7ebf00' ]
        ],
    ];

    /** @var int */
    public $headerHeight;

    public $bodyStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
    ];
    /**
     * (non-PHPdoc)
     * @see \yii\base\Object::init()
     */
    public const EXPORT = 'export';

    /** @var int  */
    public $titleStartRow;

    /** @var int  */
    public $tableStartRow;

    /** @var int  */
    private $_lastRow;

    /** @var array array[] */
    public $titleRows = [];

    /** @var array array[] */
    public $footerRows = [];

    /** @var array  */
    public $loadDataInExcelStyle = [];


    /** @var bool  */
    public $pageOrientationPortrait = true;
    public $pageFitToPage = false;

    public function init()
    {
        parent::init();
        if ($this->formatter === null) {
            $this->formatter = Yii::$app->getFormatter();
        } elseif (is_array($this->formatter)) {
            $this->formatter = Yii::createObject($this->formatter);
        }
        if (!$this->formatter instanceof Formatter) {
            throw new InvalidConfigException('The "formatter" property must be either a Format object or a configuration array.');
        }
        $this->formatter->nullDisplay = null;

        if ($this->decimalSeparator!==null) {
            $this->formatter->decimalSeparator = $this->decimalSeparator;
        }
        if ($this->thousandSeparator !== null) {
            $this->formatter->thousandSeparator = $this->thousandSeparator;
        }
    }

    public function getTitleStartRow(): int
    {
        if ($this->titleStartRow === null) {
            $this->titleStartRow = 1;
        }
        return $this->titleStartRow;
    }

    public function getStartRow(): int
    {
        if ($this->tableStartRow === null) {
            $this->tableStartRow = $this->_lastRow + 1;
        }
        return $this->tableStartRow;
    }

    public function getNextRow(): int
    {
        return $this->_lastRow + 1;
    }

    /**
     * Setting data from models
     *
     * @param $models
     * @param array $columns
     * @param array $headers
     * @param Worksheet|null $activeSheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function executeColumns(&$models, $columns = [], $headers = [],Worksheet &$activeSheet = null ): void
    {
        if ($activeSheet === null) {
            $activeSheet = $this->activeSheet;
        }

        /**
         * remove no visible columns
         */
        foreach ($columns as $key => $column) {
            if (is_array($column) && isset($column['visible']) && !$column['visible']) {
                unset($columns[$key]);
            }
        }
        $hasHeader = false;
        $row = $this->getStartRow();
        $char = 26;
        while( ( $model = array_shift( $models ) ) !== null ){
            if (empty($columns)) {
                $columns = $model->attributes();
            }
            if ($this->setFirstTitle && !$hasHeader) {
                $isPlus = false;
                $colplus = 0;
                $colnum = 1;
                $col = '';
                $headerStyleColums = [];
                foreach ($columns as $key => $column) {
                    $col = '';
                    if ($colnum > $char) {
                        $colplus ++;
                        $colnum = 1;
                        $isPlus = true;
                    }
                    if ($isPlus) {
                        $col .= chr(64 + $colplus);
                    }
                    $col .= chr(64 + $colnum);
                    $header = '';
                    if(isset($column['headerStyle'])){
                        $headerStyleColums[] = [
                            'headerStyle' => $column['headerStyle'],
                            'colrow' => $col.$row
                        ];
                    }
                    if (is_array($column)) {
                        if (isset($column['header'])) {
                            $header = $column['header'];
                        } elseif (isset($column['attribute'], $headers[$column['attribute']])) {
                            $header = $headers[$column['attribute']];
                        } elseif (isset($column['attribute'])) {
                            $header = $model->getAttributeLabel($column['attribute']);
                        }
                    } else {
                        $header = $model->getAttributeLabel($column);
                    }
                    $activeSheet->setCellValue($col . $row, $header);

                    if (isset($column['excelWidth'])) {
                        $activeSheet->getColumnDimension($col)->setWidth($column['excelWidth']);
                    } elseif (!isset($column['autoSize']) || $column['autoSize']) {
                        $activeSheet->getColumnDimension($col)->setAutoSize(true);
                    }
                    //if (isset($column['excelWrap'])) {
                        $activeSheet
                            ->getStyle($col . $row)
                            ->getAlignment()
                            ->setWrapText(true)
                        ;
                    //}
                    $colnum ++;
                }
                if ($this->headerStyle) {
                    $activeSheet
                        ->getStyle('A' . $row . ':' . $col . $row)
                        ->applyFromArray($this->headerStyle);
                }
                foreach ($headerStyleColums as $column) {
                    $activeSheet
                        ->getStyle($column['colrow'])
                        ->applyFromArray($column['headerStyle']);
                }
                if ($this->freezeHeader) {
                    $activeSheet->freezePaneByColumnAndRow(1, $row + 1);
                }
                if ($this->autoFilter) {
                    $activeSheet->setAutoFilter('A' . $row . ':' . $col . $row);
                }
                if($this->headerHeight){
                    $activeSheet->getRowDimension($row)->setRowHeight($this->headerHeight);
                }
                $hasHeader = true;
                $row++;
                $this->_lastRow++;
            }
            $isPlus = false;
            $colplus = 0;
            $colnum = 1;
            $firstCol = null;
            foreach ($columns as $key => $column) {
                $col = '';
                if ($colnum > $char) {
                    $colplus++;
                    $colnum = 1;
                    $isPlus = true;
                }
                if ($isPlus) {
                    $col .= chr(64 + $colplus);
                }
                $col .= chr(64 + $colnum);
                if(!$firstCol){
                    $firstCol = $col;
                }
                if (is_array($column)) {
                    $column_value = $this->executeGetColumnData($model, $row, $column);
                } else {
                    $column_value = $this->executeGetColumnData($model, $row, ['attribute' => $column]);
                }
                if (isset($column['format'])) {
                    $formatOptions = [];
                    if (is_array($column['format'])) {
                        $format = array_shift($column['format']);
                        $formatOptions = $column['format'];
                    } else {
                        $format = $column['format'];
                    }
                    switch ($format) {
                        case 'date':
                            $activeSheet
                                ->getStyle($col . $row)
                                ->getNumberFormat()
                                ->setFormatCode($this->dateFormat);
                            break;
                        case 'date-time':
                            if($date = DateTime::createFromFormat('Y-m-d H:i:s',$column_value,new DateTimeZone('UTC'))) {
                                $column_value = Date::PHPToExcel($date->getTimestamp());
                                $activeSheet
                                    ->getStyle($col . $row)
                                    ->getNumberFormat()
                                    ->setFormatCode($this->dateTimeFormat);
                            }
                            break;
                        case 'text':
                            if (is_string($column_value) && strpos($column_value, '0') === 0) {
                                $activeSheet
                                    ->getCell($col . $row)
                                    ->setValueExplicit($column_value, DataType::TYPE_STRING);
                                break;
                            }
                            $activeSheet
                                ->getStyle($col . $row)
                                ->getNumberFormat()
                                ->setFormatCode(NumberFormat::FORMAT_TEXT);
                            break;
                        case 'decimal':
                            $decimalFormat = NumberFormat::FORMAT_NUMBER_00;
                            if ($formatOptions) {
                                $decimalFormat = '0.' . str_repeat('0', $formatOptions[0]);
                            }
                            $activeSheet->getStyle($col . $row)
                                ->getNumberFormat()
                                ->setFormatCode($decimalFormat);
                            break;
                    }
                }

                $activeSheet->setCellValue($col . $row, $column_value);

                if (isset($column['excelWrap'])) {
                    $activeSheet
                        ->getStyle($col . $row)
                        ->getAlignment()
                        ->setWrapText($column['excelWrap'])
                    ;
                }

                $colnum++;
            }
            if ($this->bodyStyle) {
                $activeSheet
                    ->getStyle($firstCol . $row . ':' . $col . $row)
                    ->applyFromArray($this->bodyStyle);
            }
            $row++;
            $this->_lastRow ++;
        }
    }

    /**
     * Setting label or keys on every record if setFirstRecordAsKeys is true.
     * @param array $sheetData
     * @return array
     */
    public function executeArrayLabel($sheetData): array
    {
        $keys = ArrayHelper::remove($sheetData, '1');

        $new_data = [];

        foreach ($sheetData as $values) {
            $new_data[] = array_combine($keys, $values);
        }

        return $new_data;
    }

    /**
     * Leave record with same index number.
     * @param array $sheetData
     * @param array $index
     * @return array
     */
    public function executeLeaveRecords($sheetData = [], $index = []): array
    {
        foreach ($sheetData as $key => $data) {
            if (in_array($key, $index, true)) {
                unset($sheetData[$key]);
            }
        }
        return $sheetData;
    }

    /**
     * Read record with same index number.
     * @param array $sheetData
     * @param array $index
     * @return array
     */
    public function executeGetOnlyRecords($sheetData = [], $index = []): array
    {
        foreach ($sheetData as $key => $data) {
            if (!in_array($key, $index)) {
                unset($sheetData[$key]);
            }
        }
        return $sheetData;
    }

    /**
     * Getting column value.
     *
     * @param $model
     * @param int $row
     * @param array $params
     * @return bool|float|mixed|string|null
     */
    public function executeGetColumnData($model,int $row ,array $params = [])
    {
        $value = null;
        try {
            if (isset($params['value']) && $params['value'] !== null) {
                if (is_string($params['value'])) {
                    $value = ArrayHelper::getValue($model, $params['value']);
                } else {
                    $value = call_user_func($params['value'], $model, $this, $row);
                }
            } elseif (isset($params['list']) && is_array($params['list'])) {
                $value = ArrayHelper::getValue($model, $params['attribute']);
                if($value !== null && $value !== ''){
                    $value = $params['list'][$value] ?? $value . '!';
                }else{
                    $value = '';
                }
            } elseif (isset($params['attribute']) && $params['attribute'] !== null) {
                $value = ArrayHelper::getValue($model, $params['attribute']);
            }

            if (isset($params['format'])) {
                if ($params['format'] == 'date-time') {
                    return $value;
                }
                if ($params['format'] === 'date' || ($params['format'][0] ?? '') === 'date') {
                    if($value === '0000-00-00'){
                        $value = '';
                    }
                    /**
                     * if date without time, add 00:00:00 as time
                     */
                    if (strlen($value)=== 10) {
                        $value .= ' 00:00:00';
                    }
                    if (!$dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $value, new DateTimeZone('UTC'))) {
                        return $value;
                    }
                    return Date::PHPToExcel($dateTime->getTimestamp());
                }
                if ($params['format'] !== null) {
                    return $this->formatter()->format($value, $params['format']);
                }
            }
        }catch (\Exception $exception) {
            Yii::error('Exception:' . $exception->getMessage());
            if(is_object($model)) {
                Yii::error('$model: ' . VarDumper::dumpAsString($model->attributes));
            }else{
                Yii::error('$model: ' . VarDumper::dumpAsString($model));
            }
            Yii::error('$row: ' . $row);
            if($params){
                Yii::error('$params: ' . VarDumper::dumpAsString($params));
            }
            Yii::error($exception->getTraceAsString());
            return '???';
        }
        return $value;
    }

    /**
     * Populating columns for checking the column is string or array. if is string this will be checking have a formatter or header.
     * @param array $columns
     * @throws InvalidArgumentException
     * @return array
     */
    public function populateColumns($columns = []): array
    {
        $_columns = [];
        foreach ($columns as $key => $value) {
            if (is_string($value)) {
                $value_log = explode(':', $value);
                $_columns[$key] = ['attribute' => $value_log[0]];

                if (isset($value_log[1]) && $value_log[1] !== null) {
                    $_columns[$key]['format'] = $value_log[1];
                }

                if (isset($value_log[2]) && $value_log[2] !== null) {
                    $_columns[$key]['header'] = $value_log[2];
                }
            } elseif (is_array($value)) {
                if (!isset($value['attribute']) && !isset($value['value'])) {
                    throw new InvalidArgumentException('Attribute or Value must be defined.');
                }
                $_columns[$key] = $value;
            }
        }

        return $_columns;
    }

    /**
     * Formatter for i18n.
     * @return Formatter
     */
    public function formatter(): Formatter
    {
        if (!isset($this->formatter)) {
            $this->formatter = Yii::$app->getFormatter();
        }

        return $this->formatter;
    }

    /**
     * Setting header to download generated file xls
     */
    public function setHeaders(): void
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $this->getFileName() . '"');
        header('Cache-Control: max-age=0');
    }

    /**
     * Getting the file name of exporting xls file
     * @return string
     */
    public function getFileName(): string
    {
        $fileName = 'exports';
        if ($this->fileName && is_string($this->fileName)) {
            $fileName = $this->fileName;
        }

        $pathinfo = pathinfo($this->fileName);
        if (!isset($pathinfo['extension'])) {
            $extensionMap = [
                'Xlsx' => '.xlsx',
                //'Excel5' => '.xls',
                'Xls' => '.xls',
                //'Excel2003XML' => '.xml',
                //'OOCalc' => '.ods',
                //'SYLK' => '.slk',
                //'Gnumeric' => '.Gnumeric',
                'Html' => '.html',
                'Cav' => '.csv',
            ];

            if (isset($this->format)) {
                if (isset($extensionMap[$this->format])) {
                    $fileName .= $extensionMap[$this->format];
                }
            } else {
                $fileName .= $extensionMap['Xlsx'];
            }
        }

        return $fileName;
    }

    /**
     * Setting properties for excel file
     * @param Spreadsheet $objectExcel
     * @param array $properties
     */
    public function properties(&$objectExcel, $properties = []): void
    {
        foreach ($properties as $key => $value) {
            $keyname = 'set' . ucfirst($key);
            $objectExcel->getProperties()->{$keyname}($value);
        }
    }

    /**
     * saving the xls file to download or to path
     *
     * @param $sheet
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function writeFile(&$sheet): void
    {
        if (!isset($this->format)) {
            $this->format = 'Xlsx';
        }
        $objectWriter = IOFactory::createWriter($sheet, $this->format);
        $sheet = null;
        $path = 'php://output';
        if (isset($this->savePath) && $this->savePath !== null) {
            $path = $this->savePath . '/' . $this->getFileName();
        }
        $objectWriter->save($path);
        exit;

    }

    /**
     * reading the xls file
     *
     * @param $fileName
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws Exception
     */
    public function readFile($fileName): array
    {
        if (!isset($this->format)) {
            $this->format = IOFactory::identify($fileName);
        }
        $objectreader = IOFactory::createReader($this->format);
        $objectPhpExcel = $objectreader->load($fileName);

        $sheetCount = $objectPhpExcel->getSheetCount();

        $sheetDatas = [];

        if ($sheetCount > 1) {
            foreach ($objectPhpExcel->getSheetNames() as $sheetIndex => $sheetName) {
                if (isset($this->getOnlySheet) && $this->getOnlySheet !== null) {
                    if (!$objectPhpExcel->getSheetByName($this->getOnlySheet)) {
                        return $sheetDatas;
                    }
                    $objectPhpExcel->setActiveSheetIndexByName($this->getOnlySheet);
                    $indexed = $this->getOnlySheet;
                    $sheetDatas[$indexed] = $objectPhpExcel->getActiveSheet()->toArray(null, true, true, true);
                    if ($this->setFirstRecordAsKeys) {
                        $sheetDatas[$indexed] = $this->executeArrayLabel($sheetDatas[$indexed]);
                    }
                    if (!empty($this->getOnlyRecordByIndex)) {
                        $sheetDatas[$indexed] = $this->executeGetOnlyRecords($sheetDatas[$indexed], $this->getOnlyRecordByIndex);
                    }
                    if (!empty($this->leaveRecordByIndex)) {
                        $sheetDatas[$indexed] = $this->executeLeaveRecords($sheetDatas[$indexed], $this->leaveRecordByIndex);
                    }
                    return $sheetDatas[$indexed];
                }

                $objectPhpExcel->setActiveSheetIndexByName($sheetName);
                $indexed = $this->setIndexSheetByName === true ? $sheetName : $sheetIndex;
                $sheetDatas[$indexed] = $objectPhpExcel->getActiveSheet()->toArray(null, true, true, true);
                if ($this->setFirstRecordAsKeys) {
                    $sheetDatas[$indexed] = $this->executeArrayLabel($sheetDatas[$indexed]);
                }
                if (!empty($this->getOnlyRecordByIndex) && isset($this->getOnlyRecordByIndex[$indexed]) && is_array($this->getOnlyRecordByIndex[$indexed])) {
                    $sheetDatas = $this->executeGetOnlyRecords($sheetDatas, $this->getOnlyRecordByIndex[$indexed]);
                }
                if (!empty($this->leaveRecordByIndex) && isset($this->leaveRecordByIndex[$indexed]) && is_array($this->leaveRecordByIndex[$indexed])) {
                    $sheetDatas[$indexed] = $this->executeLeaveRecords($sheetDatas[$indexed], $this->leaveRecordByIndex[$indexed]);
                }

            }
        } else {
            $sheetDatas = $objectPhpExcel->getActiveSheet()->toArray(null, true, true, true);
            if ($this->setFirstRecordAsKeys) {
                $sheetDatas = $this->executeArrayLabel($sheetDatas);
            }
            if (!empty($this->getOnlyRecordByIndex)) {
                $sheetDatas = $this->executeGetOnlyRecords($sheetDatas, $this->getOnlyRecordByIndex);
            }
            if (!empty($this->leaveRecordByIndex)) {
                $sheetDatas = $this->executeLeaveRecords($sheetDatas, $this->leaveRecordByIndex);
            }
        }

        return $sheetDatas;
    }


    /**
     * @param Worksheet $worksheet
     */
    private function setPageSettings(&$worksheet): void
    {
        if($this->pageOrientationPortrait) {
            $worksheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        }else{
            $worksheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        }

        if($this->pageFitToPage){
            $worksheet->getPageSetup()->setFitToPage(true);
        }
    }

    /**
     * @see \yii\base\Widget::run()
     * @return array|string
     * @throws Exception
     * @throws InvalidConfigException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function run()
    {
        if ($this->mode === self::EXPORT) {
            $sheet = new Spreadsheet();

            if (!isset($this->models)) {
                throw new InvalidConfigException('Config models must be set');
            }

            if (isset($this->properties)) {
                $this->properties($sheet, $this->properties);
            }

            if ($this->isMultipleSheet) {
                $index = 0;
                $worksheet = [];
                foreach ($this->models as $title => $models) {
                    $sheet->createSheet($index);
                    $sheet->getSheet($index)->setTitle($title);
                    $worksheet[$index] = $sheet->getSheet($index);
                    $this->setPageSettings($worksheet[$index]);
                    $columns = $this->columns[$title] ?? [];
                    $headers = $this->headers[$title] ?? [];
                    $this->executeColumns($models, $this->populateColumns($columns), $headers, $worksheet[$index]);
                    $index++;
                }
            } else {
                $worksheet = $sheet->getActiveSheet();
                $this->setPageSettings($worksheet);
                $this->_lastRow = $this->writeRows($this->titleRows, 1, $this->getTitleStartRow(), $worksheet);
                $this->executeColumns($this->models, isset($this->columns) ? $this->populateColumns($this->columns) : [], $this->headers ?? [], $worksheet);
                $this->_lastRow = $this->writeRows($this->footerRows, 1, $this->getNextRow(), $worksheet);
            }

            if ($this->asAttachment) {
                $this->setHeaders();
            }
            $this->writeFile($sheet);
            return '';
        }
        if ($this->mode === 'import') {
            if (is_array($this->fileName)) {
                $datas = [];
                foreach ($this->fileName as $key => $filename) {
                    $datas[$key] = $this->readFile($filename);
                }
                return $datas;
            }
            return $this->readFile($this->fileName);

        }
        return '';
    }

    /**
     * @param array $rows
     * @param int $x
     * @param int $y
     * @param Worksheet $activeSheet
     * @return int last title row
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function writeRows(array $rows, int $x, int $y, &$activeSheet): int
    {
        if (!$rows) {
            return 0;
        }
        $lde = new LoadDataInExcel($activeSheet, $x, $y);
        $lde->tn->y = $y;
        $lde->classStyle = $this->loadDataInExcelStyle;
        foreach ($rows as $row) {
            $lde->fillRow($row);
            $lde->tn->newLine();
        }
        return $lde->tn->y;
    }

    /**
     * Exporting data into an excel file.
     *
     * ~~~
     *
     * \moonland\phpexcel\Excel::export([
     *        'models' => $allModels,
     *        'columns' => ['column1','column2','column3'],
     *        //without header working, because the header will be get label from attribute label.
     *        'header' => ['column1' => 'Header Column 1','column2' => 'Header Column 2', 'column3' => 'Header Column 3'],
     * ]);
     *
     * ~~~
     *
     * New Feature for exporting data, you can use this if you familiar yii gridview.
     * That is same with gridview data column.
     * Columns in array mode valid params are 'attribute', 'header', 'format', 'value', and footer (TODO).
     * Columns in string mode valid layout are 'attribute:format:header:footer(TODO)'.
     *
     * ~~~
     *
     * \moonland\phpexcel\Excel::export([
     *    'models' => Post::find()->all(),
     *        'columns' => [
     *            'author.name:text:Author Name',
     *            [
     *                    'attribute' => 'content',
     *                    'header' => 'Content Post',
     *                    'format' => 'text',
     *                    'value' => function($model) {
     *                        return ExampleClass::removeText('example', $model->content);
     *                    },
     *            ],
     *            'like_it:text:Reader like this content',
     *            'created_at:datetime',
     *            [
     *                    'attribute' => 'updated_at',
     *                    'format' => 'date',
     *            ],
     *        ],
     *        'headers' => [
     *            'created_at' => 'Date Created Content',
     *        ],
     * ]);
     *
     * ~~~
     *
     * @param array $config
     * @return string
     * @throws InvalidConfigException
     */
    public static function export($config = []): string
    {
        $config = ArrayHelper::merge(['mode' => self::EXPORT], $config);
        return self::widget($config);
    }

    /**
     * Import file excel and return into an array.
     *
     * ~~~
     *
     * $data = \moonland\phpexcel\Excel::import($fileName, ['setFirstRecordAsKeys' => true]);
     *
     * ~~~
     *
     * @param string!array $fileName to load.
     * @param array $config is a more configuration.
     * @return string
     * @throws InvalidConfigException
     */
    public static function import($fileName, $config = []): string
    {
        $config = ArrayHelper::merge(['mode' => 'import', 'fileName' => $fileName, 'asArray' => true], $config);
        return self::widget($config);
    }

    /**
     * @param array $config
     * @return string
     * @throws InvalidConfigException
     */
    public static function widget($config = []): string
    {
        if ($config['mode'] === 'import' && !isset($config['asArray'])) {
            $config['asArray'] = true;
        }

        if (isset($config['asArray']) && $config['asArray'] === true) {
            $config['class'] = static::class;
            $widget = Yii::createObject($config);
            return $widget->run();
        }

        return parent::widget($config);
    }
}
