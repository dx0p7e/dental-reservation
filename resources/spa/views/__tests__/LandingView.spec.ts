import { readFileSync } from 'fs'
import { resolve } from 'path'
import { describe, expect, it } from 'vitest'

/**
 * Structural test: verifies that the LandingView template contains the required
 * section anchor IDs added in the nav-rework-landing-page change.
 */
describe('LandingView: section anchor IDs', () => {
    const source = readFileSync(
        resolve(__dirname, '../LandingView.vue'),
        'utf-8',
    )

    it('has id="hero" on the hero section', () => {
        expect(source).toContain('id="hero"')
    })

    it('has id="services" on the services section', () => {
        expect(source).toContain('id="services"')
    })

    it('has id="loyalty" on the loyalty preview section', () => {
        expect(source).toContain('id="loyalty"')
    })

    it('has id="about" on the about section', () => {
        expect(source).toContain('id="about"')
    })

    it('has id="contact" on the contact section', () => {
        expect(source).toContain('id="contact"')
    })

    it('has id="doctors" on the doctors section', () => {
        expect(source).toContain('id="doctors"')
    })

    it('has id="testimonials" on the testimonials section', () => {
        expect(source).toContain('id="testimonials"')
    })

    it('includes the loyalty tier cards', () => {
        expect(source).toContain('loyaltyTiers')
    })

    it('includes the contact form submitContactForm handler', () => {
        expect(source).toContain('submitContactForm')
    })
})
