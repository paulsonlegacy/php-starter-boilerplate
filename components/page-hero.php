<?php
/*
 * Page Hero Component
 *
 * A simple full-width hero block with a heading and body text.
 * Demonstrates passing $data variables into a component.
 *
 * Variables (passed via component()):
 *   $title — string — the main heading text
 *   $body  — string — supporting text shown below the heading
 *
 * Usage:
 *   component('page-hero', [
 *       'title' => 'Welcome to ' . $context['setting']['name'],
 *       'body'  => 'Your landing page content goes here.',
 *   ]);
 */
?>
<section class="page-hero">
    <h1><?= e($title) ?></h1>
    <p><?= e($body) ?></p>
</section>
