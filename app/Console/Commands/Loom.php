<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;

class Loom extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loom:pull';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull from API';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    /*
        'deleteGroup'
        'updateGroup'
        'migrationFile'
 */
    public function handle()
    {
        $endpoint = config('twill.loop_api') . '/pull';
        $client = new Client();
        $response = $client->post($endpoint)->getBody()->getContents();

        $fileManifest = json_decode($response) ?? [];;

        foreach ($fileManifest->deleteGroup as $fileAssign) {
            if ($fileAssign->role != 'migration') {
                $this->deleteFile($fileAssign);

            }

        }

        return 0;
    }

    private function deleteFile($row)
    {
        $fullPath = str_replace('[base]', base_path(), $row->path);

        if (File::exists($fullPath)) {
            File::delete($fullPath);
            echo "REMOVED: " . $fullPath . "\n";
            $this->updateStatus($row->id, 'delete');
        }

    }

    private function updateStatus($id, $action)
    {

        $client = new Client();

        if ($action == 'delete') {
            $endpoint = config('twill.loop_api') . '/update';
            $response = $client->post($endpoint)->getBody()->getContents();

            $client->request('POST', $endpoint, [
                'id' => $id,
                'version_hash' => 'delete',
            ]);

        }
    }

}
