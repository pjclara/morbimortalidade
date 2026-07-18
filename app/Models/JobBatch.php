<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobBatch extends Model
{
    protected $fillable = ['cancelled_at', 'failed_job_ids', 'failed_jobs', 'finished_at', 'name', 'options', 'pending_jobs', 'total_jobs'];

    //

}