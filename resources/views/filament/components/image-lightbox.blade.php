{{--
    Full-screen click-to-preview overlay for admin images (product photos, etc).
    Registered panel-wide via a BODY_END render hook — any <img> anywhere in the
    admin can opt in by dispatching a `tavan-image-preview` window event with
    `{ detail: { src } }` (see ProductInfolist's extraImgAttributes for the trigger).

    Style is driven entirely through :style (not x-show) — Alpine's x-show only
    ever removes the `display` property when showing, which falls back to the
    browser's block default rather than the flex layout this needs.
--}}
<div
    x-data="{ open: false, src: null }"
    x-on:tavan-image-preview.window="open = true; src = $event.detail.src"
    x-on:keydown.escape.window="open = false"
    x-on:click="open = false"
    :style="'position: fixed; inset: 0; z-index: 1000; align-items: center; justify-content: center; background: rgba(0, 0, 0, 0.85); padding: 2rem; cursor: zoom-out; display: ' + (open ? 'flex' : 'none') + ';'"
>
    <img
        :src="src"
        x-on:click.stop
        style="max-width: 100%; max-height: 100%; object-fit: contain; cursor: default; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5); border-radius: 4px;"
    />

    <button
        type="button"
        x-on:click="open = false"
        style="position: fixed; top: 1.25rem; right: 1.5rem; color: #fff; background: rgba(255, 255, 255, 0.1); border: none; border-radius: 9999px; width: 40px; height: 40px; font-size: 20px; line-height: 1; cursor: pointer;"
        aria-label="Zatvori"
    >
        &times;
    </button>
</div>
