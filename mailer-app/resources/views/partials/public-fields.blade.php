{{-- Champs d'un formulaire public, partagés entre le formulaire d'email et les vues.
     Variables : $form (config normalisée), $old (closure : clé -> ancienne valeur) --}}
@foreach($form['fields'] as $field)
    @php
        $k = $field['key'];
        $name = "answers[$k]";
        $id = 'f_'.$k;
        $val = $old($k);
    @endphp
    <div class="pub-field">
        <label for="{{ $id }}">{{ $field['label'] }}@if($field['required'])<span class="req">*</span>@endif</label>

        @switch($field['type'])
            @case('textarea')
                <textarea id="{{ $id }}" name="{{ $name }}" rows="4" class="form-control @error("answers.$k") is-invalid @enderror" placeholder="{{ $field['placeholder'] }}" @required($field['required'])>{{ $val }}</textarea>
                @break

            @case('rating')
                <div><div class="stars">
                    @for($i = 5; $i >= 1; $i--)
                        <input type="radio" id="{{ $id }}_{{ $i }}" name="{{ $name }}" value="{{ $i }}" @checked((string) $val === (string) $i)>
                        <label for="{{ $id }}_{{ $i }}" title="{{ $i }}/5">★</label>
                    @endfor
                </div></div>
                @break

            @case('yesno')
                <div class="seg">
                    @foreach(['oui' => 'Oui', 'non' => 'Non'] as $v => $l)
                        <input type="radio" id="{{ $id }}_{{ $v }}" name="{{ $name }}" value="{{ $v }}" @checked($val === $v)>
                        <label for="{{ $id }}_{{ $v }}">{{ $l }}</label>
                    @endforeach
                </div>
                @break

            @case('radio')
                <div class="seg">
                    @foreach($field['options'] as $i => $opt)
                        <input type="radio" id="{{ $id }}_{{ $i }}" name="{{ $name }}" value="{{ $opt }}" @checked($val === $opt)>
                        <label for="{{ $id }}_{{ $i }}">{{ $opt }}</label>
                    @endforeach
                </div>
                @break

            @case('select')
                <select id="{{ $id }}" name="{{ $name }}" class="form-select @error("answers.$k") is-invalid @enderror">
                    <option value="">— Choisir —</option>
                    @foreach($field['options'] as $opt)<option value="{{ $opt }}" @selected($val === $opt)>{{ $opt }}</option>@endforeach
                </select>
                @break

            @case('checkbox')
                @foreach($field['options'] as $i => $opt)
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="{{ $id }}_{{ $i }}" name="{{ $name }}[]" value="{{ $opt }}" @checked(in_array($opt, (array) $val, true))>
                        <label class="form-check-label" for="{{ $id }}_{{ $i }}">{{ $opt }}</label>
                    </div>
                @endforeach
                @break

            @default
                <input id="{{ $id }}" name="{{ $name }}" value="{{ $val }}"
                       type="{{ ['number' => 'number', 'date' => 'date', 'tel' => 'tel', 'email' => 'email'][$field['type']] ?? 'text' }}"
                       class="form-control @error("answers.$k") is-invalid @enderror" placeholder="{{ $field['placeholder'] }}" @required($field['required'])>
        @endswitch

        @error("answers.$k")<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
@endforeach
