<?php

use App\Models\Post;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use TiMacDonald\Log\LogEntry;
use TiMacDonald\Log\LogFake;

uses(LazilyRefreshDatabase::class);

it('can call creating event', function () {
    $post = Post::factory()->create();

    expect($post)->slug->toEqual(str($post->title)->slug()->toString());
});

it('can call saved event', function () {
    LogFake::bind();

    $post = Post::factory()->create();

    $post->title = 'Updated Title';
    $post->save();
    Log::assertLogged(fn (LogEntry $log) => $log->level === 'debug'
        && $log->message === 'Post was updated'
    );

    $post->delete();
    $post->title = 'Updated Title again';
    $post->save();
    Log::assertLogged(fn (LogEntry $log) => $log->level === 'debug'
        && $log->message === 'Post was trashed'
    );
});
