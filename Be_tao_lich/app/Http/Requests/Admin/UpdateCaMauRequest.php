<?php

namespace App\Http\Requests\Admin;

use App\Models\CaMau;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class UpdateCaMauRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ten_ca'       => 'nullable|string|max:100',
            'name'         => 'nullable|string|max:100',
            'gio_bat_dau'  => ['nullable', 'regex:/^(?:2[0-3]|[01][0-9]):[0-5][0-9](?::[0-5][0-9])?$/'],
            'startTime'    => ['nullable', 'regex:/^(?:2[0-3]|[01][0-9]):[0-5][0-9](?::[0-5][0-9])?$/'],
            'gio_ket_thuc' => ['nullable', 'regex:/^(?:2[0-3]|[01][0-9]):[0-5][0-9](?::[0-5][0-9])?$/'],
            'endTime'      => ['nullable', 'regex:/^(?:2[0-3]|[01][0-9]):[0-5][0-9](?::[0-5][0-9])?$/'],
            'he_so_luong'  => 'nullable|numeric|min:1.0|max:3.0',
            'multiplier'   => 'nullable|numeric|min:1.0|max:3.0',
            'trang_thai'   => 'nullable|string|in:ACTIVE,PAUSED,INACTIVE,active,paused,inactive',
            'status'       => 'nullable|string|in:ACTIVE,PAUSED,INACTIVE,active,paused,inactive',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $rawId = $this->route('id') ?? $this->input('ca_mau_id') ?? $this->input('id');
            $shiftId = $rawId ? (int) preg_replace('/[^0-9]/', '', (string)$rawId) : null;

            $name = trim($this->input('ten_ca') ?? $this->input('name') ?? '');
            if (!empty($name)) {
                $query = CaMau::whereRaw('LOWER(TRIM(ten_ca)) = ?', [mb_strtolower($name, 'UTF-8')]);
                if ($shiftId) {
                    $query->where('ca_mau_id', '!=', $shiftId);
                }
                if ($query->exists()) {
                    $validator->errors()->add('ten_ca', "Đã có ca tên “{$name}”.");
                }
            }

            // Kiểm tra giờ bắt đầu và kết thúc
            $currentShift = $shiftId ? CaMau::find($shiftId) : null;
            $start = $this->input('gio_bat_dau') ?? $this->input('startTime') ?? ($currentShift ? $currentShift->gio_bat_dau : null);
            $end   = $this->input('gio_ket_thuc') ?? $this->input('endTime') ?? ($currentShift ? $currentShift->gio_ket_thuc : null);

            if ($start && $end) {
                $startFmt = substr($start, 0, 5);
                $endFmt   = substr($end, 0, 5);
                if ($startFmt === $endFmt) {
                    $validator->errors()->add('gio_ket_thuc', 'Giờ kết thúc không được trùng với giờ bắt đầu.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'gio_bat_dau.regex'   => 'Giờ bắt đầu không đúng định dạng (HH:mm).',
            'startTime.regex'     => 'Giờ bắt đầu không đúng định dạng (HH:mm).',
            'gio_ket_thuc.regex'  => 'Giờ kết thúc không đúng định dạng (HH:mm).',
            'endTime.regex'       => 'Giờ kết thúc không đúng định dạng (HH:mm).',
            'he_so_luong.numeric' => 'Hệ số lương phải là chữ số.',
            'multiplier.numeric'  => 'Hệ số lương phải là chữ số.',
            'he_so_luong.min'     => 'Hệ số lương ca phải từ 1.0 đến 3.0.',
            'multiplier.min'      => 'Hệ số lương ca phải từ 1.0 đến 3.0.',
            'he_so_luong.max'     => 'Hệ số lương ca phải từ 1.0 đến 3.0.',
            'multiplier.max'      => 'Hệ số lương ca phải từ 1.0 đến 3.0.',
            'trang_thai.in'       => 'Trạng thái không hợp lệ (hỗ trợ: ACTIVE, PAUSED, INACTIVE).',
            'status.in'           => 'Trạng thái không hợp lệ (hỗ trợ: active, paused, inactive).',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status'  => false,
            'message' => $validator->errors()->first(),
            'errors'  => $validator->errors(),
        ], 422);

        throw new ValidationException($validator, $response);
    }
}
