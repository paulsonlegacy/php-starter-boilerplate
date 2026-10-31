<section>
    <h1>Sign In</h1>
    <form method="POST" action="<?= e(ROOT) ?>/login">
        <input type="hidden" name="csrf_token" value="<?= e($context['csrf_token']) ?>">

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Sign In</button>
    </form>
</section>
