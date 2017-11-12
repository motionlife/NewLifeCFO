<?php

namespace newlifecfo\Models;

use Illuminate\Database\Eloquent\Model;
use newlifecfo\Models\Templates\Contact;
use newlifecfo\User;

class Outreferrer extends Model
{
    protected $guarded = [];
    public function fullname()
    {
        return $this->first_name.' '.$this->last_name;
    }
    //all the developed clients
    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    //Get the contact info
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    //Get the corresponding system user of this outside referrer
    public function user()
    {
        return $this->belongsTo(User::class)->withDefault([
            'first_name' => 'Unregistered',
            'last_name' => 'Unregistered',
            'priority' => 0
        ]);
    }

}
