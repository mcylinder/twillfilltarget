<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class Loom
{


    public function syncDatabase()
    {

        $fileContents = DB::connection('loomysql')->table('file_contents')->get();
        $fileStats = [];
        foreach ($fileContents as $fileContent) {

            if ($fileContent->content_hash != '' && $fileContent->content_hash != $fileContent->version_hash) {
                $fileStats[] = $this->createFile($fileContent);
            }

            if ($fileContent->content_hash == '') {
                $fileStats[] = $this->deleteFile($fileContent);
            }
        }
        foreach ($fileStats as $fileStat) {
            DB::connection('loomysql')->table('file_contents')->where('id',
                $fileStat['id'])->update(['version_hash' => $fileStat['version_hash']]);
        }

        return true;
    }


    private function deleteFile($row)
    {
        $fileStatus = ['id' => null, 'version_hash' => ''];
        $fullPath = str_replace('[base]', base_path(), $row->path);

        if (File::exists($fullPath)) {
            File::delete($fullPath);
            //    echo "REMOVED: " . $fullPath . "\n";
            $fileStatus['id'] = $row->id;
        }
        return $fileStatus;

    }


    private function createFile($fileAssign)
    {
        $fileStatus = ['id' => null, 'version_hash' => ''];
        $fullPath = str_replace('[base]', base_path(), $fileAssign->path);
        $sections = explode('/', $fullPath);
        array_pop($sections);
        $directory = implode('/', $sections);

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0777, true);
        }

        $status = File::put($fullPath, $fileAssign->content, true);
        if ($status && isset($fileAssign->id)) {
            $fileStatus['id'] = $fileAssign->id;
            $fileStatus['version_hash'] = hash("crc32", $fileAssign->content);
        }

        return $fileStatus;
    }

}
