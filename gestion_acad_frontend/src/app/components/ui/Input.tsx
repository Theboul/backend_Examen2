import React from 'react';

interface InputProps {
  label: string;
  type?: 'text' | 'number' | 'date';
  value: string | number;
  onChange: (value: string) => void;
}

export const Input: React.FC<InputProps> = ({
  label,
  type = 'text',
  value,
  onChange
}) => {
  return (
    <div style={{ marginBottom: '16px' }}>
      <label style={{ display: 'block', marginBottom: '4px' }}>
        {label}
      </label>
      <input
        type={type}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        style={{
          width: '100%',
          padding: '8px',
          border: '1px solid #ddd',
          borderRadius: '4px'
        }}
      />
    </div>
  );
};