<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Image extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'path'
    ];

    protected $appends = ['url'];

    // Polymorphic relationship: An image belongs to an imageable model
    public function imageable()
    {
        return $this->morphTo();
    }

    /**
     * Get the full URL of the image.
     *
     * @return string
     */
    public function getUrlAttribute()
    {
//        return asset('storage/' . $this->path);

        $publicStoragePath = config('filesystems.public_storage_path');

        return rtrim($publicStoragePath, '/') . '/' . ltrim($this->path, '/');
    }
}
