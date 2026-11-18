<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * F1 (2026-11-19): the R5 fab/sticky-bar script froze the tab.
 *
 * Root cause: a MutationObserver watched the WHOLE document.body
 * ({ attributes, attributeFilter: ['class'], childList, subtree }) and
 * its callback unconditionally re-ran apply(), which itself changes
 * classes on #floating-actions / #sticky-bottom-bar inside body — a
 * self-feeding observer loop. Every class/DOM change on the page
 * (fade-up reveals, slider + gallery autoplay, count-up, wizard steps)
 * re-triggered it.
 *
 * Fix: observe ONLY the modal elements (class attribute, no subtree),
 * only apply() when the modal state really changed, and make apply()
 * idempotent (check classList.contains before add/remove). The
 * dynamically-inserted reschedule backdrop notifies the script
 * explicitly via window.meashFabRefresh / closeWebReschedule().
 */
class HomepageFreezeObserverTest extends TestCase
{
    private function bladeHtml(): string
    {
        return file_get_contents(resource_path('views/website.blade.php'));
    }

    private function r5Script(): string
    {
        $blade = $this->bladeHtml();
        $start = strpos($blade, 'R5: fab + sticky bar behaviour');
        $this->assertNotFalse($start, 'R5 script block not found');
        $end = strpos($blade, '</script>', $start);
        return substr($blade, $start, $end - $start);
    }

    public function test_only_one_mutation_observer_in_the_whole_blade(): void
    {
        $this->assertSame(1, substr_count($this->bladeHtml(), 'new MutationObserver'));
    }

    public function test_observer_never_watches_document_body_or_subtree(): void
    {
        $blade = $this->bladeHtml();
        $this->assertStringNotContainsString('observe(document.body', $blade);
        $this->assertStringNotContainsString('subtree: true', $blade);
        $this->assertStringNotContainsString('childList: true', $blade);
    }

    public function test_observer_watches_only_the_modal_elements(): void
    {
        $script = $this->r5Script();
        $this->assertStringContainsString("'staff-auth-modal'", $script);
        $this->assertStringContainsString("'mobile-nav-drawer'", $script);
        $this->assertStringContainsString("document.querySelector('.ms-lb')", $script);
        $this->assertStringContainsString("attributeFilter: ['class']", $script);
        $this->assertStringContainsString('new MutationObserver(refreshModalState)', $script);
    }

    public function test_apply_only_runs_when_modal_state_really_changed(): void
    {
        $script = $this->r5Script();
        $this->assertStringContainsString('if (m !== modalOpen) { modalOpen = m; apply(); }', $script);
        // The old unconditional callback must be gone.
        $this->assertStringNotContainsString('modalOpen = anyModalOpen(); apply();', $script);
    }

    public function test_apply_is_idempotent_via_set_cls(): void
    {
        $script = $this->r5Script();
        $this->assertStringContainsString('function setCls(el, cls, on)', $script);
        $this->assertStringContainsString('if (!el.classList.contains(cls)) el.classList.add(cls);', $script);
        $this->assertStringContainsString("setCls(fab, 'ms-fab-visible', show);", $script);
        $this->assertStringContainsString("setCls(bar, 'ms-bar-hidden', !show);", $script);
        // No bare toggles left on the fab/bar in apply().
        $this->assertStringNotContainsString("fab.classList.toggle('ms-fab-visible'", $script);
        $this->assertStringNotContainsString("bar.classList.toggle('ms-bar-hidden'", $script);
    }

    public function test_reschedule_backdrop_notifies_fab_state_explicitly(): void
    {
        $blade = $this->bladeHtml();
        $this->assertStringContainsString('function closeWebReschedule()', $blade);
        $this->assertStringContainsString('window.meashFabRefresh = refreshModalState;', $blade);
        // All three inline removal handlers go through closeWebReschedule().
        $this->assertGreaterThanOrEqual(3, substr_count($blade, 'closeWebReschedule()'));
        $this->assertStringNotContainsString("document.getElementById('web-reschedule-backdrop').remove()", $blade);
        // openWebRescheduleModal refreshes state after inserting the backdrop.
        $this->assertStringContainsString('if (window.meashFabRefresh) window.meashFabRefresh();', $blade);
    }
}
