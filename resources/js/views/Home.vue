<template>
    <div class="home container">
        <div class="hero">
            <h1>Master Your Preflop Game</h1>
            <p class="hero-subtitle">
                Train MTT preflop decisions with smart mistake tracking and weighted randomization
                that focuses on your problem areas.
            </p>
            <div class="hero-actions">
                <router-link to="/drill" class="btn btn-primary btn-lg">Start Drilling</router-link>
                <router-link to="/scenarios" class="btn btn-secondary btn-lg">Manage Scenarios</router-link>
            </div>
        </div>

        <div class="features">
            <div class="feature card">
                <div class="feature-icon">🎯</div>
                <h3>Smart Randomization</h3>
                <p>Hands you struggle with appear more often. Border hands get extra focus.</p>
            </div>
            <div class="feature card">
                <div class="feature-icon">📊</div>
                <h3>Detailed Stats</h3>
                <p>Track your progress with heatmaps and mistake breakdowns.</p>
            </div>
            <div class="feature card">
                <div class="feature-icon">⚡</div>
                <h3>Quick Drills</h3>
                <p>Keyboard shortcuts (F/C/R) for fast, efficient practice sessions.</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.home {
    padding-top: var(--spacing-xl);
}

.hero {
    text-align: center;
    margin-bottom: var(--spacing-xl);
    padding: var(--spacing-xl);
}

.hero h1 {
    font-size: 2.5rem;
    margin-bottom: var(--spacing-md);
}

.hero-subtitle {
    font-size: 1.125rem;
    color: var(--color-text-secondary);
    max-width: 600px;
    margin: 0 auto var(--spacing-lg);
}

.hero-actions {
    display: flex;
    gap: var(--spacing-md);
    justify-content: center;
}

.features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--spacing-lg);
}

.feature {
    text-align: center;
}

.feature-icon {
    font-size: 2.5rem;
    margin-bottom: var(--spacing-md);
}

.feature h3 {
    margin-bottom: var(--spacing-sm);
}

.feature p {
    color: var(--color-text-secondary);
}
</style>
