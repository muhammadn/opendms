<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClusterData extends Model
{
    protected $fillable = ['duck_id', 'topic', 'message_id', 'payload', 'path', 'hops', 'duck_type'];
    //
}
