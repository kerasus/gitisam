<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SamanTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'mid',
        'state',
        'status',
        'rrn',
        'ref_num',
        'res_num',
        'terminal_id',
        'trace_no',
        'wage',
        'secure_pan',
        'hashed_card_number',
        'result_code',
        'result_description',
        'success',
        'original_amount',
        'affective_amount',
        's_trace_date',
        's_trace_no',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }
}
