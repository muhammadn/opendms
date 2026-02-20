<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;
use Illuminate\Support\Facades\Log;

class MqttSubscribe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mqtt-subscribe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
      Log::info("Processing MQTT messages...");
      $mqtt = MQTT::connection();
      $mqtt->subscribe('hub/event', function (string $topic, string $message) {
        dispatch(new \App\Jobs\ProcessMqttMessage($message));
      }, 0);
      $mqtt->loop(true);
    }
}
