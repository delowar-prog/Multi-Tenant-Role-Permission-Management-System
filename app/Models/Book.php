<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    /** @use HasFactory<\Database\Factories\BookFactory> */
    use HasFactory;
    protected $fillable = [
        'title',
        'isbn',
        'author_id',
        'publisher_id',
        'category_id',
        'published_year',
        'copies_total',
        'copies_available',
    ];

    public function author() { return $this->belongsTo(Author::class); }
    public function publisher() { return $this->belongsTo(Publisher::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function copies() { return $this->hasMany(BookCopy::class); }
    public function borrows() { return $this->hasMany(Borrow::class); }
}
