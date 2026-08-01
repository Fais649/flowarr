<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */

namespace App\Models{
    use Carbon\CarbonImmutable;

    /**
     * @property int $id
     * @property int $library_job_id
     * @property string|null $worker_id
     * @property string $file_path
     * @property ExecutionStatus $status
     * @property int|null $started_at
     * @property int|null $finished_at
     * @property CarbonImmutable|null $created_at
     * @property CarbonImmutable|null $updated_at
     * @property-read LibraryJob $libraryJob
     *
     * @method static \Database\Factories\ExecutionFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution query()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution whereFilePath($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution whereFinishedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution whereLibraryJobId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution whereStartedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution whereStatus($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution whereUpdatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution whereWorkerId($value)
     *
     * @mixin \Eloquent
     */
    class Execution extends \Eloquent {}
}

namespace App\Models{
    use Carbon\CarbonImmutable;

    /**
     * @property int $id
     * @property string $job_type
     * @property int $max_concurrent
     * @property CarbonImmutable|null $created_at
     * @property CarbonImmutable|null $updated_at
     *
     * @method static \Database\Factories\JobWorkerLimitFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder<static>|JobWorkerLimit newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|JobWorkerLimit newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|JobWorkerLimit query()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|JobWorkerLimit whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|JobWorkerLimit whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|JobWorkerLimit whereJobType($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|JobWorkerLimit whereMaxConcurrent($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|JobWorkerLimit whereUpdatedAt($value)
     *
     * @mixin \Eloquent
     */
    class JobWorkerLimit extends \Eloquent {}
}

namespace App\Models{
    use Carbon\CarbonImmutable;
    use Illuminate\Database\Eloquent\Collection;

    /**
     * @property int $id
     * @property string $base_path
     * @property LibraryStatus $status
     * @property int $scan_interval
     * @property int|null $last_scan
     * @property CarbonImmutable|null $created_at
     * @property CarbonImmutable|null $updated_at
     * @property-read Collection<int, LibraryJob> $libraryJobs
     * @property-read int|null $library_jobs_count
     * @property-read Collection<int, Worker> $workers
     * @property-read int|null $workers_count
     *
     * @method static Builder<static>|Library dueForScan()
     * @method static \Database\Factories\LibraryFactory factory($count = null, $state = [])
     * @method static Builder<static>|Library newModelQuery()
     * @method static Builder<static>|Library newQuery()
     * @method static Builder<static>|Library query()
     * @method static Builder<static>|Library whereBasePath($value)
     * @method static Builder<static>|Library whereCreatedAt($value)
     * @method static Builder<static>|Library whereId($value)
     * @method static Builder<static>|Library whereLastScan($value)
     * @method static Builder<static>|Library whereScanInterval($value)
     * @method static Builder<static>|Library whereStatus($value)
     * @method static Builder<static>|Library whereUpdatedAt($value)
     *
     * @mixin \Eloquent
     */
    class Library extends \Eloquent {}
}

namespace App\Models{
    use Carbon\CarbonImmutable;
    use Illuminate\Database\Eloquent\Collection;

    /**
     * @property int $id
     * @property int $library_id
     * @property LibraryJobId $job_id
     * @property CarbonImmutable|null $created_at
     * @property CarbonImmutable|null $updated_at
     * @property-read Collection<int, Execution> $executions
     * @property-read int|null $executions_count
     * @property-read Library|null $library
     *
     * @method static \Database\Factories\LibraryJobFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob query()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob whereJobId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob whereLibraryId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob whereUpdatedAt($value)
     *
     * @mixin \Eloquent
     */
    class LibraryJob extends \Eloquent {}
}

namespace App\Models{
    use Carbon\CarbonImmutable;

    /**
     * @property int $id
     * @property string $key
     * @property string|null $value
     * @property CarbonImmutable|null $created_at
     * @property CarbonImmutable|null $updated_at
     *
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting query()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereKey($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereUpdatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereValue($value)
     *
     * @mixin \Eloquent
     */
    class Setting extends \Eloquent {}
}

namespace App\Models{
    use Illuminate\Database\Eloquent\Collection;
    use Illuminate\Notifications\DatabaseNotification;
    use Illuminate\Notifications\DatabaseNotificationCollection;
    use Laravel\Fortify\Contracts\PasskeyUser;
    use Laravel\Passkeys\Passkey;

    /**
     * @property int $id
     * @property string $name
     * @property string $email
     * @property Carbon|null $email_verified_at
     * @property string $password
     * @property string|null $two_factor_secret
     * @property string|null $two_factor_recovery_codes
     * @property Carbon|null $two_factor_confirmed_at
     * @property string|null $remember_token
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
     * @property-read int|null $notifications_count
     * @property-read Collection<int, Passkey> $passkeys
     * @property-read int|null $passkeys_count
     *
     * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
     *
     * @mixin \Eloquent
     */
    class User extends \Eloquent implements \Laravel\Passkeys\Contracts\PasskeyUser, PasskeyUser {}
}

namespace App\Models{
    use Carbon\CarbonImmutable;
    use Illuminate\Database\Eloquent\Collection;

    /**
     * @property int $id
     * @property string $name
     * @property CarbonImmutable|null $created_at
     * @property CarbonImmutable|null $updated_at
     * @property LibraryJobId|null $job_type
     * @property int $concurrency
     * @property bool $replace_original
     * @property bool $enabled
     * @property-read Collection<int, Library> $libraries
     * @property-read int|null $libraries_count
     *
     * @method static \Database\Factories\WorkerFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker query()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker whereConcurrency($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker whereEnabled($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker whereJobType($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker whereName($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker whereReplaceOriginal($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker whereUpdatedAt($value)
     *
     * @mixin \Eloquent
     */
    class Worker extends \Eloquent {}
}
