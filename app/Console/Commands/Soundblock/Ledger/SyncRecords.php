<?php

namespace App\Console\Commands\Soundblock\Ledger;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Contracts\Soundblock\Ledger;
use App\Helpers\Filesystem\Soundblock;
use App\Contracts\Core\Slack as SlackService;
use App\Repositories\Soundblock\Ledger as LedgerRepository;
use App\Models\Soundblock\{
    Accounts\Account as AccountModel,
    Collections\Collection as CollectionModel,
    Files\File as FileModel,
    Projects\Project as ProjectModel,
    Projects\Contracts\Contract as ContractModel,
    Projects\Deployments\Deployment as DeploymentModel,
    Tracks\Track as TrackModel
};
use App\Jobs\Soundblock\Ledger\{
    CollectionLedger,
    ContractLedger as ContractLedgerJob,
    DeploymentLedger,
    FileLedger,
    ProjectLedger,
    ServiceLedger,
    TrackLedger
};
use App\Services\Soundblock\Ledger\{
    CollectionLedger as CollectionLedgerService,
    ContractLedger as ContractLedgerService,
    DeploymentLedger as DeploymentLedgerService,
    ServiceLedger as ServiceLedgerService,
    TrackLedger as TrackLedgerService
};

class SyncRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "ledger:sync";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "This command is made for sync ledger records after python service goes down.";
    private array $arrUserMeta;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->arrUserMeta = [
            "remote_addr" => request()->getClientIp(),
            "remote_host" => gethostbyaddr(request()->getClientIp()),
            "remote_agent" => request()->server("HTTP_USER_AGENT")
        ];
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param LedgerRepository $ledgerRepo
     * @param Ledger $ledgerService
     * @return int
     */
    public function handle(LedgerRepository $ledgerRepo, Ledger $ledgerService)
    {
        try {
            /* Get records for sync from soundblock_ledger table */
            $objLedgerForSync = $ledgerRepo->getRecordsForSync();

            foreach ($objLedgerForSync as $objLedger) {
                /* Prepare data to send to qldb */
                $arrData = $objLedger->metadata()->value("data_json");

                if (!empty($arrData)) {
                    /* Insert qldb record */
                    $arrInsertedData = $ledgerService->insertDocument($objLedger->qldb_table, array_merge([
                        "Blockchain Ledger ID" => $objLedger->ledger_uuid,
                    ], $arrData));

                    /* Update soundblock_ledger table with qldb response */
                    $objLedger->update([
                        "qldb_id"       => $arrInsertedData["document"]["id"],
                        "qldb_block"    => $arrInsertedData["document"]["blockAddress"],
                        "qldb_hash"     => $arrInsertedData["document"]["hash"],
                        "qldb_metadata" => $arrInsertedData["document"]["metadata"],
                        "qldb_data"     => $arrInsertedData["document"],
                    ]);
                }
            }
        } catch (\Exception $exception) {
            $objSlackService = resolve(SlackService::class);
            $objSlackService->syncLedgerFailedReport($exception->getMessage(), "soundblock_ledger");
        }

        $this->checkAccountsTable();
        $this->checkCollectionsTable();
        $this->checkFilesTable();
        $this->checkProjectsTable();
        $this->checkContractsTable();
        $this->checkDeploymentsTable();
        $this->checkTracksTable();

        return 0;
    }

    private function checkAccountsTable(){
        try {
            $objAccounts = AccountModel::whereNull("ledger_id")->get();

            foreach ($objAccounts as $objAccount) {
                if ($objAccount->stamp_created_at->diffInHours(Carbon::now()) > 1) {
                    dispatch(new ServiceLedger($objAccount, ServiceLedgerService::CREATE_EVENT, $this->arrUserMeta))->onQueue("ledger");
                }
            }
        } catch (\Exception $exception) {
            $objSlackService = resolve(SlackService::class);
            $objSlackService->syncLedgerFailedReport($exception->getMessage(), "soundblock_accounts");
        }
    }

    private function checkCollectionsTable(){
        try {
            $objCollections = CollectionModel::whereNull("ledger_id")->get();

            foreach ($objCollections as $objCollection) {
                if ($objCollection->stamp_created_at->diffInHours(Carbon::now()) > 1) {
                    dispatch(new CollectionLedger($objCollection, CollectionLedgerService::CREATE_EVENT, $this->arrUserMeta))->onQueue("ledger");
                }
            }
        } catch (\Exception $exception) {
            $objSlackService = resolve(SlackService::class);
            $objSlackService->syncLedgerFailedReport($exception->getMessage(), "soundblock_collections");
        }
    }

    private function checkFilesTable(){
        try {
            $objFiles = FileModel::whereNull("ledger_id")->get();

            foreach ($objFiles as $objFile) {
                if ($objFile->stamp_created_at->diffInHours(Carbon::now()) > 1) {
                    dispatch(new FileLedger($objFile, Soundblock::project_file_path($objFile->collections()->first()->project, $objFile), $this->arrUserMeta))->onQueue("ledger");
                }
            }
        } catch (\Exception $exception) {
            $objSlackService = resolve(SlackService::class);
            $objSlackService->syncLedgerFailedReport($exception->getMessage(), "soundblock_files");
        }
    }

    private function checkProjectsTable(){
        try {
            $objProjects = ProjectModel::whereNull("ledger_id")->get();

            foreach ($objProjects as $objProject) {
                if ($objProject->stamp_created_at->diffInHours(Carbon::now()) > 1) {
                    dispatch(new ProjectLedger($objProject, \App\Services\Soundblock\Ledger\ProjectLedger::CREATE_EVENT, $this->arrUserMeta))->onQueue("ledger");
                }
            }
        } catch (\Exception $exception) {
            $objSlackService = resolve(SlackService::class);
            $objSlackService->syncLedgerFailedReport($exception->getMessage(), "soundblock_projects");
        }
    }

    private function checkContractsTable(){
        try {
            $objContracts = ContractModel::whereNull("ledger_id")->get();

            foreach ($objContracts as $objContract) {
                if ($objContract->stamp_created_at->diffInHours(Carbon::now()) > 1) {
                    dispatch(new ContractLedgerJob($objContract, ContractLedgerService::NEW_CONTRACT_EVENT, $this->arrUserMeta))->onQueue("ledger");
                }
            }
        } catch (\Exception $exception) {
            $objSlackService = resolve(SlackService::class);
            $objSlackService->syncLedgerFailedReport($exception->getMessage(), "soundblock_projects_contracts");
        }
    }

    private function checkDeploymentsTable(){
        try {
            $objDeployments = DeploymentModel::whereNull("ledger_id")->get();

            foreach ($objDeployments as $objDeployment) {
                if ($objDeployment->stamp_created_at->diffInHours(Carbon::now()) > 1) {
                    dispatch(new DeploymentLedger($objDeployment, DeploymentLedgerService::NEW_DEPLOYMENT, $this->arrUserMeta))->onQueue("ledger");
                }
            }
        } catch (\Exception $exception) {
            $objSlackService = resolve(SlackService::class);
            $objSlackService->syncLedgerFailedReport($exception->getMessage(), "soundblock_projects_deployments");
        }
    }

    private function checkTracksTable(){
        try {
            $objTracks = TrackModel::whereNull("ledger_id")->get();

            foreach ($objTracks as $objTrack) {
                if ($objTrack->stamp_created_at->diffInHours(Carbon::now()) > 1) {
                    dispatch(new TrackLedger($objTrack, TrackLedgerService::CREATE_EVENT, $this->arrUserMeta))->onQueue("ledger");
                }
            }
        } catch (\Exception $exception) {
            $objSlackService = resolve(SlackService::class);
            $objSlackService->syncLedgerFailedReport($exception->getMessage(), "soundblock_tracks");
        }
    }
}
