<?php

namespace App;

use DB;
use Laravel\Cashier\Billable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property User $user
 * @property Collection|Activity[] $activities
 * @property Collection|ActitivyStatistic[] $activityStatistics
 * @property Collection|Contact[] $contacts
 * @property Collection|Invitation[] $invitations
 * @property Collection|Debt[] $debts
 * @property Collection|Entry[] $entries
 * @property Collection|Gift[] $gifts
 * @property Collection|Event[] $events
 * @property Collection|Kid[] $kids
 * @property Collection|Note[] $notes
 * @property Collection|Reminder[] $reminders
 * @property Collection|SignificantOther[] $significantOthers
 * @property Collection|Task[] $tasks
 */
class Account extends Model
{

    use Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'number_of_invitations_sent'
    ];

    /**
     * Get the activity records associated with the account.
     *
     * @return HasMany
     */
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Get the contact records associated with the account.
     *
     * @return HasMany
     */
    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get the invitations associated with the account.
     *
     * @return HasMany
     */
    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * Get the debt records associated with the account.
     *
     * @return HasMany
     */
    public function debts()
    {
        return $this->hasMany(Debt::class);
    }

    /**
     * Get the gift records associated with the account.
     *
     * @return HasMany
     */
    public function gifts()
    {
        return $this->hasMany(Gift::class);
    }

    /**
     * Get the event records associated with the account.
     *
     * @return HasMany
     */
    public function events()
    {
        return $this->hasMany(Event::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the kid records associated with the account.
     *
     * @return HasMany
     */
    public function kids()
    {
        return $this->hasMany(Kid::class);
    }

    /**
     * Get the note records associated with the account.
     *
     * @return HasMany
     */
    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    /**
     * Get the reminder records associated with the account.
     *
     * @return HasMany
     */
    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * Get the task records associated with the account.
     *
     * @return HasMany
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the user records associated with the account.
     *
     * @return HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the activity statistics record associated with the account.
     *
     * @return HasMany
     */
    public function activityStatistics()
    {
        return $this->hasMany(ActivityStatistic::class);
    }

    /**
     * Get the task records associated with the account.
     *
     * @return HasMany
     */
    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    /**
     * Get the task records associated with the account.
     *
     * @return HasMany
     */
    public function significantOthers()
    {
        return $this->hasMany(SignificantOther::class);
    }

    /**
     * Check if the account can be downgraded, based on a set of rules
     *
     * @return this
     */
    public function canDowngrade()
    {
        $canDowngrade = true;
        $numberOfUsers = $this->users()->count();
        $numberPendingInvitations = $this->invitations()->count();

        // number of users in the account should be == 1
        if ($numberOfUsers > 1) {
            $canDowngrade = false;
        }

        // there should not be any pending user invitations
        if ($numberPendingInvitations > 0) {
            $canDowngrade = false;
        }

        return $canDowngrade;
    }

    /**
     * Check if the account is currently subscribed to a plan
     *
     * @return boolean $isSubscribed
     */
    public function isSubscribed()
    {
        $isSubscribed = false;

        if ($this->subscribed(config('monica.paid_plan_friendly_name'))) {
            $isSubscribed = true;
        }

        return $isSubscribed;
    }

    /**
     * Check if the account has invoices linked to this account.
     * This was created because Laravel Cashier doesn't know how to properly
     * handled the case when a user doesn't have invoices yet. This sucks balls.
     *
     * @return boolean
     */
    public function hasInvoices()
    {
        $query = DB::table('subscriptions')->where('account_id', $this->id)->count();
        if ($query > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get the next billing date for the account
     *
     * @return String $timestamp
     */
    public function getNextBillingDate()
    {
        // Weird method to get the next billing date from Laravel Cashier
        // see https://stackoverflow.com/questions/41576568/get-next-billing-date-from-laravel-cashier
        $timestamp = $this->asStripeCustomer()["subscriptions"]
                            ->data[0]["current_period_end"];

        return \App\Helpers\DateHelper::getShortDate($timestamp);
    }
}
