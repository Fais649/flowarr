<?php

use App\Models\User;
use App\Models\Worker;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('lists workers', function () {
    Worker::factory()->count(2)->create();

    $this->get('/workers')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('workers/index'));
});

it('shows worker detail', function () {
    $worker = Worker::factory()->create();

    $this->get("/workers/{$worker->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('workers/[id]/index'));
});
