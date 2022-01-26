<?php

namespace App\Utils\Download;

use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class Manager
{

    public static function generateDownloadFile($content, $downloadName, $prefix = '', $expires = 20)
    {
        self::gcDownloadFiles();

        $sessionId = Session::getId();

        $expireTime = (new Carbon())->addSeconds($expires);


        $fileName = storage_path('tmp/downloads/' . $prefix . '_' . $sessionId . '_' . $expireTime->getTimestamp());

        file_put_contents($fileName, $content);

        $request = resolve('Illuminate\Http\Request');

        $request->session()->put('download', [
            "realName" => $fileName,
            "downloadName" => $downloadName
        ]);

        return $fileName;
    }

    public static function gcDownloadFiles()
    {
        $Directory = new \RecursiveDirectoryIterator(storage_path('tmp/downloads'));
        $Iterator = new \RecursiveIteratorIterator($Directory);

        foreach ($Iterator as $key => $value) {

            $parts = explode('_', $value->getFileName());

            if (isset($parts[2])) {
                $current = new Carbon();

                $expire = Carbon::createFromTimestamp((int)$parts[2]);

                if ($current->gte($expire)) {
                    unlink($key);
                }
            }
        }
    }

    public static function downloadFile()
    {
        $request = resolve('Illuminate\Http\Request');

        $download = $request->session()->pull('download', []);

        if ($download && isset($download["realName"]) && isset($download["downloadName"])) {

            $realName = $download["realName"];
            $downloadName = $download["downloadName"];

            $content = file_get_contents($realName);

            $size = filesize($realName);

            unlink($realName);

            $headers = ['Content-type' => 'text/plain',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $downloadName),
                'Content-Length' => $size];
            return \Illuminate\Support\Facades\Response::make($content, 200, $headers);

        } else {
            throw new Exception(trans('errors.noFileDownload'), 404);
        }
    }
}
