<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::all()->map(fn (Book $book) => $book->toArray());

        return view('books.catalog', compact('books'));
    }

    public function create()
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'integer', 'min:1', 'unique:books,code'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'genre_id' => ['required', 'exists:genres,id'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        $validated['genre'] = Genre::findOrFail($validated['genre_id'])->name;
        $book = Book::create($validated);

        Log::info('Book saved', [
            'id' => $book->id,
        ]);

        return redirect()
            ->route('books.details', $book->id)
            ->with('success', 'Book added successfully.');
    }

    public function show(Book $book)
    {
        return view('books.details', compact('book'));
    }

    public function edit(Book $book)
    {
        $genres = Genre::all();

        return view('books.edit', compact('book', 'genres'));
    }

    public function update(Request $request, Book $book)
    {
        if ($request->input('_action') === 'old-input-redirect') {
            return redirect()
                ->route('books.edit', $book)
                ->withInput($request->except(['_token', '_method', '_action']))
                ->with('status', 'Form values restored. Changes were not saved.');
        }

        $validator = Validator::make($request->all(), [
            'code' => ['required', 'integer', 'min:1', 'unique:books,code,'.$book->id],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'genre_id' => ['required', 'exists:genres,id'],
            'price' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('books.edit', $book)
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $validated['genre'] = Genre::findOrFail($validated['genre_id'])->name;
        $book->update($validated);

        return redirect()
            ->route('books.catalog')
            ->with('success', 'Book updated successfully.');
    }

    public function destroy(Book $book) {}

    public function spotlight()
    {
        $selectedBook = Book::findOrFail(104);

        return view('books.details', [
            'book' => $selectedBook,
            'isSpotlight' => true,
        ]);
    }

    public function categoryFilter($category = null)
    {
        $query = Book::query();

        if ($category) {
            $query->whereRaw('LOWER(genre) = ?', [strtolower($category)]);
        }

        return view('books.catalog', [
            'books' => $query->get()->map(fn (Book $book) => $book->toArray()),
            'currentCategory' => $category,
        ]);
    }
}
