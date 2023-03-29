<?php

namespace App\Console\Commands\Soundblock;

use App\Repositories\Soundblock\Data\ExchangeRates as ExchangeRatesRepo;
use Carbon\Carbon;
use Illuminate\Console\Command;
use KubAT\PhpSimple\HtmlDomParser;
use App\Contracts\Core\Slack as SlackService;

class UpdateExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:current_exchange_rates';

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
        try {
            $objExchangeRepo = resolve(ExchangeRatesRepo::class);
            $currentDate = Carbon::now()->format("Y-m-d");
            $arrFindingCurrencies = ["aud" => "Australian Dollar", "gbp" => "British Pound", "eur" => "Euro"];

            $objCurl = curl_init();
            curl_setopt_array($objCurl, [
                CURLOPT_URL => "https://www.x-rates.com/historical/?from=USD&amount=1&date=" . $currentDate,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "authority: www.x-rates.com",
                    "cache-control: max-age=0",
                    "dnt: 1",
                    "upgrade-insecure-requests: 1",
                    "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
                    "user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36",
                    "content-type: application/x-www-form-urlencoded; charset=UTF-8",
                    "origin: https://www.x-rates.com",
                    "sec-fetch-site: same-origin",
                    "sec-fetch-mode: navigate",
                    "sec-fetch-dest: document",
                    "accept-language: en-US,en;q=0.9,ru;q=0.8,uk;q=0.7",
                    "cookie: grfz=pmhgyrvhzofgrhza1625136281"
                ),
            ]);

            $strCurl = curl_exec($objCurl);
            curl_close($objCurl);

            $html = HtmlDomParser::str_get_html($strCurl);
            $ret = $html->find("table.tablesorter.ratesTable>tbody>tr");
            $arrUpdateExchangeRate = [];

            foreach ($ret as $t) {
                $strCurr = $t->find("td")[0]->plaintext;
                if (in_array($strCurr, $arrFindingCurrencies)) {
                    $strCurrCode = array_search($strCurr, $arrFindingCurrencies);
                    $floatCurrRate = $t->find("td")[1]->find("a", 0)->plaintext;
                    $arrUpdateExchangeRate["usd_to_" . $strCurrCode] = $floatCurrRate;
                }
            }

            $arrUpdateExchangeRate["exchange_date"] = $currentDate;
            $objExchangeRepo->crateOrUpdate($arrUpdateExchangeRate);
        } catch (\Exception $exception) {
            $slackService->reportExchangeRates($exception->getMessage());
        }
    }
}
