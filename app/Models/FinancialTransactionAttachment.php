<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FinancialTransactionAttachment extends Model
{
    protected $fillable = ['transaction_id', 'path', 'file_type', 'uploaded_by'];

    // Relation vers la transaction
    public function transaction()
    {
        return $this->belongsTo(FinancialTransaction::class);
    }

    // Retourne le nom du fichier (sans le chemin)
    public function getFilenameAttribute()
    {
        return basename($this->path);
    }

    // Retourne l'URL publique du fichier
    public function getUrlAttribute()
    {
        return Storage::disk('public')->url($this->path);
    }
}
