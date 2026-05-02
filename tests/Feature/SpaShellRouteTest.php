<?php

test('spa shell route returns 200 for public homepage', function () {
    $this->get(route('home'))->assertOk();
});

test('spa shell route returns 200 for login page', function () {
    $this->get(route('login'))->assertOk();
});

test('spa shell catch-all route returns 200 for unknown paths', function () {
    $this->get('/appointments')->assertOk();
});
