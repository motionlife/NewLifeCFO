<?php

namespace newlifecfo\Models;

use Illuminate\Database\Eloquent\Model;
use newlifecfo\Models\Templates\Contact;

class Outreferrer extends Model
{
    protected $guarded = [];

    //all the developed clients
    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    //Get the contact info
    public function contact()
    {
        return $this->hasOne(Contact::class,'cc_id');
    }

    //Get the corresponding system user of this client
    public function user()
    {
        return $this->belongsTo(User::class)->withDefault([
            'first_name' => 'Unregistered',
            'last_name' => 'Unregistered',
            'priority' => 0
        ]);
    }

}
