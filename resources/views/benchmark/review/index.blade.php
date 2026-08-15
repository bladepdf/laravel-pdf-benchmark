<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fidelity review · {{ $run }}</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, sans-serif; color: #14203f; }
        body { background: #f3f5fa; margin: 0; }
        header { background: #112047; color: white; padding: 24px 4vw; position: sticky; top: 0; z-index: 5; }
        header h1 { margin: 0 0 4px; } header p { margin: 0; opacity: .7; }
        main { margin: 28px auto; max-width: 1500px; padding: 0 24px; }
        .entry { background: white; border: 1px solid #dbe1ef; border-radius: 18px; margin-bottom: 24px; overflow: hidden; scroll-margin-top: 110px; }
        .entry[data-reviewed="true"] { border-color: #9dd9c6; }
        .entry-head { align-items: center; display: flex; gap: 14px; justify-content: space-between; padding: 18px 20px; }
        .entry-head h2 { font-size: 18px; margin: 0; } .meta { color: #68738d; font-size: 13px; }
        .images { background: #1b2337; display: grid; gap: 1px; grid-template-columns: repeat(3, minmax(0, 1fr)); }
        figure { background: #252f47; margin: 0; padding: 12px; } figcaption { color: white; font-size: 12px; margin-bottom: 8px; }
        figure img { background: white; display: block; height: 430px; object-fit: contain; width: 100%; }
        form { align-items: center; display: grid; gap: 12px; grid-template-columns: 180px 1fr 1fr auto minmax(90px, auto); padding: 18px 20px; }
        select, input, button { border: 1px solid #cbd3e3; border-radius: 9px; font: inherit; padding: 10px; }
        button { background: #2557d6; border-color: #2557d6; color: white; cursor: pointer; font-weight: 700; }
        button:disabled { cursor: wait; opacity: .65; }
        .save-state { color: #68738d; font-size: 13px; min-width: 90px; }
        .save-state[data-state="saved"] { color: #12604c; }
        .save-state[data-state="error"] { color: #a12626; }
        .save-state[data-state="dirty"] { color: #8a5a00; }
        .saved { background: #d8fff1; border-radius: 8px; color: #12604c; margin-bottom: 18px; padding: 12px; }
        @media (max-width: 900px) { .images, form { grid-template-columns: 1fr; } figure img { height: auto; } }
    </style>
</head>
<body>
<header><h1>Fidelity review</h1><p>{{ $run }} · Browsershot is a comparison reference, not an automatic truth source.</p></header>
<main>
    @if(session('saved'))<div class="saved">Saved {{ session('saved') }}</div>@endif
    @foreach($review['entries'] as $entry)
        @php($document = $fidelity['documents'][$entry['renderer'].'__'.$entry['template'].'__'.$entry['asset_mode']] ?? null)
        @php($page = $document ? collect($document['pages'] ?? [])->firstWhere('page', $entry['page'] ?? 1) : null)
        @php($metric = $page['metrics']['features'][$entry['feature']] ?? null)
        <article class="entry" id="{{ $entry['key'] }}" data-reviewed="{{ $entry['status'] === null ? 'false' : 'true' }}">
            <div class="entry-head"><div><h2>{{ $entry['label'] }}</h2><div class="meta">{{ $entry['renderer'] }} · {{ $entry['template'] }} · {{ $entry['asset_mode'] }} · page {{ $entry['page'] ?? 1 }}@if($metric) · crop difference {{ number_format($metric['difference_ratio'] * 100, 3) }}%@endif</div></div>@if(isset($document['pdf']))<a href="{{ route('benchmark.artifact', ['run' => $run, 'path' => $document['pdf']]) }}">Download PDF</a>@endif</div>
            @if($page)
            <div class="images">
                <figure><figcaption>Browsershot reference</figcaption><img loading="lazy" src="{{ route('benchmark.artifact', ['run' => $run, 'path' => $page['reference']]) }}"></figure>
                <figure><figcaption>Target</figcaption><img loading="lazy" src="{{ route('benchmark.artifact', ['run' => $run, 'path' => $page['target']]) }}"></figure>
                <figure><figcaption>Pixel diff</figcaption><img loading="lazy" src="{{ route('benchmark.artifact', ['run' => $run, 'path' => $page['diff']]) }}"></figure>
            </div>
            @else
            <p style="padding: 0 20px; color: #a33;">No comparable page image is available. Inspect the PDF/error and classify the feature explicitly.</p>
            @endif
            <form method="post" action="{{ route('benchmark.review.update', ['run' => $run]) }}" data-review-form>
                @csrf
                <input type="hidden" name="key" value="{{ $entry['key'] }}">
                <select name="status" required><option value="">Choose result…</option>@foreach(['pass' => 'Pass', 'partial' => 'Partial', 'fail' => 'Fail'] as $value => $label)<option value="{{ $value }}" @selected($entry['status'] === $value)>{{ $label }}</option>@endforeach</select>
                <input name="problem" value="{{ $entry['problem'] }}" placeholder="Observed problem (if any)">
                <input name="note" value="{{ $entry['note'] }}" placeholder="Reviewer note">
                <button type="submit">Save</button>
                <span class="save-state" data-save-state data-state="{{ $entry['status'] === null ? 'idle' : 'saved' }}" role="status" aria-live="polite">{{ $entry['status'] === null ? '' : 'Saved' }}</span>
            </form>
        </article>
    @endforeach
</main>
<script>
    const saveQueue = { current: Promise.resolve() };

    document.querySelectorAll('[data-review-form]').forEach((form) => {
        const state = form.querySelector('[data-save-state]');
        const button = form.querySelector('button[type="submit"]');
        const status = form.elements.status;
        const problem = form.elements.problem;

        const updateProblemRequirement = () => {
            problem.required = status.value === 'partial' || status.value === 'fail';
        };

        updateProblemRequirement();
        status.addEventListener('change', updateProblemRequirement);

        form.addEventListener('input', () => {
            state.dataset.state = 'dirty';
            state.textContent = 'Unsaved changes';
            form.closest('.entry').dataset.reviewed = 'false';
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            updateProblemRequirement();

            if (!form.reportValidity()) {
                return;
            }

            button.disabled = true;
            state.dataset.state = 'idle';
            state.textContent = 'Queued…';

            saveQueue.current = saveQueue.current.then(async () => {
                state.textContent = 'Saving…';

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        const validationMessage = Object.values(payload.errors ?? {}).flat()[0];
                        throw new Error(validationMessage ?? payload.message ?? `Save failed (${response.status})`);
                    }

                    state.dataset.state = 'saved';
                    state.textContent = 'Saved';
                    form.closest('.entry').dataset.reviewed = 'true';
                } catch (error) {
                    state.dataset.state = 'error';
                    state.textContent = error instanceof Error ? error.message : 'Save failed';
                } finally {
                    button.disabled = false;
                }
            });
        });
    });
</script>
</body>
</html>
