<?php
/**
 * Created for checking if billing files are exist for the month and member
 */
namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;

class SearchInvoicePdf
{
    public static $basepath= "monthly_invoices/";

    private static $detailBillFormat= "{member_code}-{month}-{year}billdetail";

    private static $summaryBillFormat= "{member_code}-{month}-{year}bill";

    private static $months= [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December'
        ];

    
    public static function isBillUploaded(string $monthlyFolderPath="")
    {
        $monthlyFolderPath= strtoupper($monthlyFolderPath);
        
        return Storage::exists(self::$basepath . $monthlyFolderPath);
    }

    public static function getDetailBillLink(string $memberCode, string $monthlyFolderPath="")
    {
        return self::getBillLink(
            $memberCode,
            $monthlyFolderPath,
            self::$detailBillFormat,
            'member.download'
        );
    }

    public static function getSummaryBillLink(string $memberCode, string $monthlyFolderPath="")
    {
        return self::getBillLink(
            $memberCode,
            $monthlyFolderPath,
            self::$summaryBillFormat,
            'member.download'
        );
    }
    public static function getDetailBillLinkApp(string $memberCode, string $monthlyFolderPath="")
    {
        return self::getBillLink(
            $memberCode,
            $monthlyFolderPath,
            self::$detailBillFormat,
            'download'
        );
    }
    public static function getSummaryBillLinkApp(string $memberCode, string $monthlyFolderPath="")
    {
        return self::getBillLink(
            $memberCode,
            $monthlyFolderPath,
            self::$summaryBillFormat,
            'download'
        );
    }

    private static function getBillLink(
        string $memberCode,
        string $monthlyFolderPath,
        string $billFormat,
        string $routeName
    ) {
        $extract = preg_split('/\s+/', trim(strtoupper($monthlyFolderPath)));

        if (count($extract) !== 2) {
            return null;
        }

        [$month, $year] = $extract;

        $billMonth = null;

        foreach (self::$months as $candidate) {
            if (stripos($candidate, $month) === 0) {
                $billMonth = strtoupper($candidate);
                break;
            }
        }

        if ($billMonth === null) {
            return null;
        }

        $fileName = strtr($billFormat, [
            '{member_code}' => $memberCode,
            '{month}'       => $billMonth,
            '{year}'        => $year,
        ]);

        $folder = $month . '_' . $year;
        $storedPath = self::$basepath . $folder . '/' . $fileName . '.PDF';

        if (! Storage::exists($storedPath)) {
            return null;
        }

        return route($routeName, [
            'month' => $month,
            'year' => $year,
            'filename' => $fileName . '.PDF',
        ]);
    }
}
