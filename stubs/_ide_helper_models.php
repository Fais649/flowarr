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
/**
 * @property int $id
 * @property int $library_job_id
 * @property string $worker_id
 * @property string $file_path
 * @property \App\ExecutionStatus $status
 * @property int|null $started_at
 * @property int|null $finished_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\LibraryJob|null $libraryJob
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
 */
	class Execution extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $base_path
 * @property \App\LibraryStatus $status
 * @property int $scan_interval
 * @property int|null $last_scan
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LibraryJob> $libraryJobs
 * @property-read int|null $library_jobs_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library dueForScan()
 * @method static \Database\Factories\LibraryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library whereBasePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library whereLastScan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library whereScanInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library whereUpdatedAt($value)
 */
	class Library extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $library_id
 * @property \App\LibraryJobId $job_id
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Execution> $executions
 * @property-read int|null $executions_count
 * @property-read \App\Models\Library|null $library
 * @method static \Database\Factories\LibraryJobFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob whereJobId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob whereLibraryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob whereUpdatedAt($value)
 */
	class LibraryJob extends \Eloquent {}
}

namespace App\Models{
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
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Passkeys\Passkey> $passkeys
 * @property-read int|null $passkeys_count
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
 */
	class User extends \Eloquent implements \Laravel\Fortify\Contracts\PasskeyUser, \Laravel\Passkeys\Contracts\PasskeyUser {}
}

