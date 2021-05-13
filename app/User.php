<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Cashier\Billable;
use App\SubscriptionOptions;

class User extends Authenticatable
{
    use Notifiable;
    use Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function getCurrentSubscription() {
      return $this->subscription("basic-access");
    }

    public function getCurrentStripePlanName() {
      return "super";
      return $this->getCurrentSubscription()->stripe_plan;
    }

    public function onGracePeriodDefaultSubscription() {
      $subscription = $this->getCurrentSubscription();
      if(isset($subscription)) {
        return $subscription->onGracePeriod();
      }
      return null;
    }

    public function onBasicPlan() {
      return false;
      return strpos($this->getCurrentStripePlanName(), "basic") !== false;
    }

    public function onPlusPlan() {
      return strpos($this->getCurrentStripePlanName(), "plus") !== false;
    }

    public function onSuperPlan() {
      return strpos($this->getCurrentStripePlanName(), "super") !== false;
    }

    public function getCurrentPlanName() {
      if($this->onSuperPlan()) {
        return "Super";
      }
      if($this->onPlusPlan()) {
        return "Plus";
      }
      return "Basic";
    }
}
