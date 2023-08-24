<?php
/**
 * Statamic v3 User Migrator
 * @author Simon Hamp <simon.hamp@me.com>
 * @copyright Copyright (c) 2021, Simon Hamp
 * @license MIT
 */
namespace App\Console\Commands;

use Exception;
use TypeError;
use Carbon\Carbon;
use Statamic\Facades\User;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use App\Models\User as UserModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Statamic\Auth\File\User as FileUser;
use Statamic\Auth\UserRepositoryManager;
use Statamic\Auth\Eloquent\User as DatabaseUser;
use Symfony\Component\Console\Output\OutputInterface;
use Statamic\Stache\Repositories\UserRepository as StacheUserRepository;

class MigrateUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:migrate
        {--f|--force : Overwrite users in the database if they exist there already}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate your users from the filesystem to the database';

    /**
     * The column to search on to see if a user already exists in the database.
     *
     * @var string
     */
    protected $searchColumn = 'email';

    /**
     * The field on the file-based user record to find in the database $searchColumn.
     *
     * @var string
     */
    protected $searchField = 'email';

    protected $errors = [];

    protected $success = 0;

    protected $skipped = 0;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $force = $this->option('force');

        User::swap(app(StacheUserRepository::class));

        try {
            $fileUsers = User::all();
        } catch (TypeError $e) {
            $this->error("Make sure your 'users' Stache store is configured in config/statamic/stache.php");
            return 1;
        }

        User::swap(app(UserRepositoryManager::class)->repository());

        $total = $fileUsers->count();

        $this->line("Found {$total} user records to migrate...");
        $bar = false;

        if ($this->getOutput()->getVerbosity() <= OutputInterface::VERBOSITY_VERBOSE) {
            $bar = $this->output->createProgressBar($total);

            $bar->start();
        }

        $fileUsers->each(function (FileUser $fileUser) use ($force, $bar) {
            $databaseUser = $this->prepDbUser($fileUser);

            if ($databaseUser->model()->exists) {
                $identifier = $this->searchValue($fileUser);

                if ($this->getOutput()->isVeryVerbose()) {
                    $this->warn("User [{$identifier}] already exists!" . ($force ? ' Overwriting...' : ''));
                }

                if (! $force) {
                    $this->skipped++;
                    return;
                }
            }

            if ($this->getOutput()->isVeryVerbose()) {
                $this->line('Migrating user: ' . $fileUser->email());
            }

            if ($this->migrate($fileUser, $databaseUser) && $this->getOutput()->isVeryVerbose()) {
                $this->info('User migrated! ID: ' . $databaseUser->getAuthIdentifier());
            }

            if ($bar) {
                $bar->advance();
            }
        });

        if ($bar) {
            $bar->finish();
            $this->newLine();
        }

        $this->newLine();
        $this->info("Successfully migrated {$this->success} out of {$total} users.");
        $this->line("Skipped {$this->skipped} users.");

        if (! empty($this->errors)) {
            $count = count($this->errors);

            $this->newLine();

            $this->warn("However, there were {$count} errors!");

            if ($this->getOutput()->isVerbose()) {
                $table = collect($this->errors)
                    ->flatMap(fn ($errors, $index) => collect($errors)->transform(fn ($error) => [$index, Str::limit($error)]));

                $this->table(['ID', 'Errors'], $table->all());

                $this->newLine();
            } else {
                $this->line("Use the `-v` option to see more details or check your log.");
            }

            return 1;
        }

        return 0;
    }

    protected function migrate(FileUser $fileUser, DatabaseUser $databaseUser): bool
    {
        // Standard fields
        /**
         * =============
         * A note on IDs
         * =============
         *
         * In case you need to keep referring to your Statamic users by their UUIDs, you'll need
         * to import this value too bu uncommenting the following line.
         *
         * You will need to have adjusted the migration to support this column _before_ migrating:
         * $table->uuid('statamic_id')->unique()
         *
         * And you may want to modify your User model to set this as the primary key:
         *
         * @see https://laravel.com/docs/8.x/eloquent#primary-keys
         */
        //$databaseUser->set('statamic_id', $fileUser->id());
        $databaseUser->set('name', $fileUser->name());
        $databaseUser->set('email', $fileUser->email());
        $databaseUser->set('super', (bool) $fileUser->isSuper());
        $databaseUser->model()->password = $fileUser->passwordHash();

        // Your custom fields here...

        // Roles
        foreach ($fileUser->roles() as $role) {
            $databaseUser->assignRole($role);
        }

        try {
            $databaseUser->save();

            $this->success++;

            return true;
        } catch (QueryException $e) {
            $this->logError($fileUser, $e->getMessage());
        }

        return false;
    }

    protected function prepDbUser(FileUser $fileUser): DatabaseUser
    {
        $model = UserModel::where($this->searchColumn, $this->searchValue($fileUser))->firstOrNew();

        /** @var DatabaseUser $user */
        $user = DatabaseUser::fromModel($model);

        return $user;
    }

    protected function searchValue(FileUser $fileUser): string
    {
        return $fileUser->fluentlyGetOrSet($this->searchField)->args([]);
    }

    protected function logError(FileUser $fileUser, string $error): void
    {
        $identifier = $this->searchValue($fileUser);
        $this->errors[$identifier][] = $error;
        if ($this->getOutput()->isVeryVerbose()) {
            $this->error("Failed to migrate user [{$identifier}]!");
        }
        Log::error("Failed to migrate user [{$identifier}]: {$error}");
    }
}