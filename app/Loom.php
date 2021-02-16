<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class Loom
{


    public function syncDatabase()
    {

        $deleteFiles = DB::connection('loomysql')->table('file_contents')->where('content_hash', '')->get();

        foreach ($deleteFiles as $deletFile) {
            $this->deleteFile($deletFile);
        }

        $createFiles = DB::connection('loomysql')->table('file_contents')->whereColumn('content_hash', '!=', 'version_hash')->where('content_hash', '!=', '')->get();

        foreach ($createFiles as $createFile) {
            $this->createFile($createFile);
        }

    }


    private function deleteFile($row)
    {
        $fullPath = str_replace('[base]', base_path(), $row->path);

        if (File::exists($fullPath)) {
            File::delete($fullPath);
        //    echo "REMOVED: " . $fullPath . "\n";
            $this->updateStatus($row->id, '');
        }

    }

    private function updateStatus($id, $version_hash)
    {
        DB::connection('loomysql')->table('file_contents')->where('id', $id)->update(['version_hash' => $version_hash]);
    }

    private function createFile($fileAssign)
    {

        $fullPath = str_replace('[base]', base_path(), $fileAssign->path);
        $sections = explode('/', $fullPath);
        array_pop($sections);
        $directory = implode('/', $sections);

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0777, true);
        }

        $status = File::put($fullPath, $fileAssign->content, true);
        if ($status && isset($fileAssign->id)) {
            $this->updateStatus($fileAssign->id, hash("crc32", $fileAssign->content));
        }

    }

}
