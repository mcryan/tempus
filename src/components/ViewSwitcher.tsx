import React from 'react';
import styled from 'styled-components';

interface ViewSwitcherProps {
  currentView: 'list' | 'calendar';
  onViewChange: (view: 'list' | 'calendar') => void;
}

const SwitcherContainer = styled.div`
  display: flex;
  gap: 8px;
  padding: 8px;
`;

const SwitcherButton = styled.button<{ active: boolean }>`
  padding: 8px 16px;
  border: 1px solid #ddd;
  border-radius: 4px;
  background: ${props => props.active ? '#007bff' : '#fff'};
  color: ${props => props.active ? '#fff' : '#333'};
  cursor: pointer;
  
  &:hover {
    background: ${props => props.active ? '#0056b3' : '#f0f0f0'};
  }
`;

export const ViewSwitcher: React.FC<ViewSwitcherProps> = ({ currentView, onViewChange }) => {
  return (
    <SwitcherContainer>
      <SwitcherButton 
        active={currentView === 'list'} 
        onClick={() => onViewChange('list')}
      >
        List view
      </SwitcherButton>
      <SwitcherButton 
        active={currentView === 'calendar'} 
        onClick={() => onViewChange('calendar')}
      >
        Calendar view
      </SwitcherButton>
    </SwitcherContainer>
  );
}; 