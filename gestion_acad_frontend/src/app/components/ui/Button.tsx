import React from 'react';

interface ButtonProps {
  children: React.ReactNode;
  variant?: 'primary' | 'secondary' | 'danger';
  onClick?: () => void;
  disabled?: boolean;
  type?: 'button' | 'submit' | 'reset';
}

export const Button: React.FC<ButtonProps> = ({
  children,
  variant = 'secondary',
  onClick,
  disabled = false,
  type = 'button'
}) => {
  const getStyle = () => {
    const base = {
      padding: '8px 16px',
      border: 'none',
      borderRadius: '4px',
      cursor: 'pointer'
    };

    const variants = {
      primary: { backgroundColor: '#1e40af', color: 'white' },
      secondary: { backgroundColor: '#6b7280', color: 'white' },
      danger: { backgroundColor: '#dc2626', color: 'white' }
    };

    return { ...base, ...variants[variant] };
  };

  return (
    <button
      type={type}
      onClick={onClick}
      disabled={disabled}
      style={getStyle()}
    >
      {children}
    </button>
  );
};