<?php

namespace App\Console\Commands\Soundblock\Data;

use App\Contracts\Core\Slack as SlackService;
use App\Repositories\Soundblock\Data\AllExchangeRates as AllExchangeRatesRepo;
use Illuminate\Console\Command;
use Exception;

class GetAllExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "apilayer:get:rates";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @param SlackService $slackService
     * @return int
     */
    public function handle(SlackService $slackService)
    {
        if (env("APP_ENV") == "prod") {
            try {
                $objExchangeRepo = resolve(AllExchangeRatesRepo::class);
                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://api.apilayer.com/exchangerates_data/latest?base=USD",
                    CURLOPT_HTTPHEADER => array(
                        "Content-Type: text/plain",
                        "apikey: " . env("APILAYER_API_KEY")
                    ),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET"
                ));

                $response = curl_exec($curl);

                curl_close($curl);

                $response = json_decode($response, true);

                if (count($response["rates"]) < 167) {
                    throw new Exception("Count of currencies less than 167.");
                }

                foreach ($response["rates"] as $strCode => $floatRate) {
                    $arrUpdateExchangeRate["data_exchange_date"] = $response["date"];
                    $arrUpdateExchangeRate["data_currency_code"] = $strCode;
                    $arrUpdateExchangeRate["data_rate"] = $floatRate;

                    $objExchangeRepo->crateOrUpdate($arrUpdateExchangeRate);
                }
            } catch (Exception $exception) {
                $slackService->reportAllExchangeRates($exception->getMessage());
            }
        }
    }
}
