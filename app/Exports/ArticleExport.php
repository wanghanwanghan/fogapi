<?php

namespace App\Exports;

use App\Model\Community\ArticleModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
//新增两个 use 比如单元格格式化、自动适应、设置宽高、导出图片、多 sheet 表等功能
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
//自适应宽高
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
//设置sheet名字
use Maatwebsite\Excel\Concerns\WithTitle;
//事件
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\BeforeWriting;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Events\AfterSheet;
//导出图片
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
//解决数据中有0时导出为空
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class ArticleExport implements FromCollection,WithColumnFormatting,ShouldAutoSize,WithTitle,WithEvents,WithDrawings,WithStrictNullComparison
{
    protected $data;

    //注册事件
    public function registerEvents(): array
    {
        return [
            AfterSheet::class=>function(AfterSheet $event)
            {
                //设置列宽
                $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(50);

                //设置行高，$i为数据行数
                for ($i=0;$i<=1265;$i++)
                {
                    $event->sheet->getDelegate()->getRowDimension($i)->setRowHeight(50);
                }

                //设置区域单元格垂直居中
                $event->sheet->getDelegate()->getStyle('A1:K1265')->getAlignment()->setVertical('center');

                //设置区域单元格字体、颜色、背景等，其他设置请查看 applyFromArray 方法，提供了注释
                $event->sheet->getDelegate()->getStyle('A1:K6')->applyFromArray([
                    'font'=>[
                        'name'=>'Arial',
                        'bold'=>true,
                        'italic'=>false,
                        'strikethrough'=>false,
                        'color'=>[
                            'rgb'=>'808080'
                        ]
                    ],
                    'fill'=>[
                        'fillType'=>'linear', //线性填充，类似渐变
                        'rotation'=>45, //渐变角度
                        'startColor'=>[
                            'rgb'=>'000000' //初始颜色
                        ],
                        'endColor'=>[
                            'argb'=>'000000' //结束颜色，如果需要单一背景色，请和初始颜色保持一致
                        ]
                    ]
                ]);

                //合并单元格
                $event->sheet->getDelegate()->mergeCells('A1:B1');
            }
        ];
    }

    //构造函数传值
    public function __construct($data)
    {
        $this->data=$data;
    }

    //数组转集合
    public function collection()
    {
        return new Collection($this->createData());
    }

    //业务代码
    public function createData()
    {
        ArticleModel::suffix(Carbon::now()->year);

        return ArticleModel::all();
    }

    public function columnFormats(): array
    {
        return [
            'V' => NumberFormat::FORMAT_DATE_DDMMYYYY, //日期
            'W' => NumberFormat::FORMAT_DATE_DDMMYYYY, //日期
            'L' => NumberFormat::FORMAT_TEXT, //导出长数字，防止变科学计数法
        ];
    }

    public function title(): string
    {
        return Carbon::now()->format('Ymd');
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('This is my logo');
        $drawing->setPath(public_path('PayToMe.jpg'));
        $drawing->setHeight(50);
        $drawing->setCoordinates('B3');

        $drawing2 = new Drawing();
        $drawing2->setName('Other image');
        $drawing2->setDescription('This is a second image');
        $drawing2->setPath(public_path('PayToMe.jpg'));
        $drawing2->setHeight(120);
        $drawing2->setCoordinates('G2');

        return [$drawing,$drawing2];
    }
}
