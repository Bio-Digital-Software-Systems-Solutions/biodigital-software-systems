import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import Chat from '../Index';

// Mock @inertiajs/react
jest.mock('@inertiajs/react', () => ({
  Head: ({ title }: { title: string }) => <title>{title}</title>,
  usePage: () => ({
    props: {
      chatRooms: [
        {
          id: 1,
          name: 'Test Room',
          type: 'direct',
          created_by: 1,
          participants: [
            { id: 1, first_name: 'John', last_name: 'Doe', full_name: 'John Doe', email: 'john@test.com' },
            { id: 2, first_name: 'Jane', last_name: 'Smith', full_name: 'Jane Smith', email: 'jane@test.com' }
          ],
          lastMessage: {
            id: 1,
            content: 'Hello there',
            created_at: '2025-08-22T10:00:00Z',
            sender: { id: 1, first_name: 'John', last_name: 'Doe', full_name: 'John Doe' }
          },
          updated_at: '2025-08-22T10:00:00Z'
        }
      ],
      users: [
        { id: 2, first_name: 'Jane', last_name: 'Smith', full_name: 'Jane Smith', email: 'jane@test.com' },
        { id: 3, first_name: 'Bob', last_name: 'Johnson', full_name: 'Bob Johnson', email: 'bob@test.com' }
      ],
      auth: {
        user: { id: 1, first_name: 'John', last_name: 'Doe', email: 'john@test.com' }
      }
    }
  }),
  router: {
    get: jest.fn(),
    post: jest.fn(),
    put: jest.fn(),
    delete: jest.fn()
  }
}));

// Mock DashboardLayout
jest.mock('@/Layouts/DashboardLayout', () => {
  return function DashboardLayout({ children }: { children: React.ReactNode }) {
    return <div data-testid="dashboard-layout">{children}</div>;
  };
});

// Mock fetch
global.fetch = jest.fn();

const mockFetch = fetch as jest.MockedFunction<typeof fetch>;

describe('Chat Component', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    // Mock CSRF token meta tag
    const meta = document.createElement('meta');
    meta.setAttribute('name', 'csrf-token');
    meta.setAttribute('content', 'test-csrf-token');
    document.head.appendChild(meta);
  });

  afterEach(() => {
    document.head.innerHTML = '';
  });

  it('renders chat interface correctly', () => {
    render(<Chat />);
    
    expect(screen.getByText('Messages')).toBeInTheDocument();
    expect(screen.getByText('Test Room')).toBeInTheDocument();
    expect(screen.getByText('Hello there')).toBeInTheDocument();
  });

  it('displays empty state when no chat rooms', () => {
    // Mock empty chat rooms
    const mockUsePage = jest.requireMock('@inertiajs/react').usePage;
    mockUsePage.mockReturnValue({
      props: {
        chatRooms: [],
        users: [],
        auth: { user: { id: 1, first_name: 'John', last_name: 'Doe', email: 'john@test.com' } }
      }
    });

    render(<Chat />);
    
    expect(screen.getByText('Aucune conversation')).toBeInTheDocument();
    expect(screen.getByText('Créez votre première conversation')).toBeInTheDocument();
  });

  it('opens new chat modal when plus button is clicked', async () => {
    const user = userEvent.setup();
    render(<Chat />);
    
    const plusButton = screen.getByRole('button', { name: /\+/ });
    await user.click(plusButton);
    
    expect(screen.getByText('Nouvelle conversation')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Rechercher des utilisateurs...')).toBeInTheDocument();
  });

  it('loads messages when a chat room is selected', async () => {
    const user = userEvent.setup();
    
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        messages: [
          {
            id: 1,
            content: 'Test message',
            created_at: '2025-08-22T10:00:00Z',
            sender: { id: 1, first_name: 'John', last_name: 'Doe', full_name: 'John Doe' },
            is_read: false
          }
        ]
      })
    } as Response);

    render(<Chat />);
    
    const chatRoom = screen.getByText('Test Room');
    await user.click(chatRoom);
    
    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledWith('/chat/rooms/1/messages', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': 'test-csrf-token',
        },
        credentials: 'same-origin',
      });
    });
  });

  it('sends message when form is submitted', async () => {
    const user = userEvent.setup();
    
    // First mock loading messages
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ messages: [] })
    } as Response);

    // Then mock sending message
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        message: {
          id: 2,
          content: 'New test message',
          created_at: '2025-08-22T10:01:00Z',
          sender: { id: 1, first_name: 'John', last_name: 'Doe', full_name: 'John Doe' },
          is_read: false
        }
      })
    } as Response);

    render(<Chat />);
    
    // Select chat room first
    const chatRoom = screen.getByText('Test Room');
    await user.click(chatRoom);
    
    await waitFor(() => {
      expect(screen.getByPlaceholderText('Tapez votre message...')).toBeInTheDocument();
    });

    // Type and send message
    const messageInput = screen.getByPlaceholderText('Tapez votre message...');
    await user.type(messageInput, 'New test message');
    
    const sendButton = screen.getByRole('button', { name: /send/i });
    await user.click(sendButton);
    
    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledWith('/chat/rooms/1/messages', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': 'test-csrf-token',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ content: 'New test message' })
      });
    });
  });

  it('creates new chat room', async () => {
    const user = userEvent.setup();
    
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        room: {
          id: 2,
          name: 'Jane Smith',
          type: 'direct',
          created_by: 1,
          participants: [
            { id: 1, first_name: 'John', last_name: 'Doe', full_name: 'John Doe', email: 'john@test.com' },
            { id: 2, first_name: 'Jane', last_name: 'Smith', full_name: 'Jane Smith', email: 'jane@test.com' }
          ],
          updated_at: '2025-08-22T10:02:00Z'
        }
      })
    } as Response);

    render(<Chat />);
    
    // Open new chat modal
    const plusButton = screen.getByRole('button', { name: /\+/ });
    await user.click(plusButton);
    
    // Select a user
    const janeCheckbox = screen.getByRole('checkbox', { name: /jane smith/i });
    await user.click(janeCheckbox);
    
    // Create chat
    const createButton = screen.getByText('Créer');
    await user.click(createButton);
    
    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledWith('/chat/rooms', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': 'test-csrf-token',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          type: 'direct',
          participant_ids: [2]
        })
      });
    });
  });

  it('filters users in new chat modal', async () => {
    const user = userEvent.setup();
    render(<Chat />);
    
    // Open new chat modal
    const plusButton = screen.getByRole('button', { name: /\+/ });
    await user.click(plusButton);
    
    // Search for user
    const searchInput = screen.getByPlaceholderText('Rechercher des utilisateurs...');
    await user.type(searchInput, 'jane');
    
    expect(screen.getByText('Jane Smith')).toBeInTheDocument();
    expect(screen.queryByText('Bob Johnson')).not.toBeInTheDocument();
  });

  it('closes new chat modal when cancel is clicked', async () => {
    const user = userEvent.setup();
    render(<Chat />);
    
    // Open new chat modal
    const plusButton = screen.getByRole('button', { name: /\+/ });
    await user.click(plusButton);
    
    expect(screen.getByText('Nouvelle conversation')).toBeInTheDocument();
    
    // Close modal
    const cancelButton = screen.getByText('Annuler');
    await user.click(cancelButton);
    
    expect(screen.queryByText('Nouvelle conversation')).not.toBeInTheDocument();
  });

  it('handles fetch errors gracefully', async () => {
    const user = userEvent.setup();
    const consoleSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
    
    mockFetch.mockRejectedValueOnce(new Error('Network error'));

    render(<Chat />);
    
    const chatRoom = screen.getByText('Test Room');
    await user.click(chatRoom);
    
    await waitFor(() => {
      expect(consoleSpy).toHaveBeenCalledWith('Failed to load messages:', expect.any(Error));
    });
    
    consoleSpy.mockRestore();
  });

  it('handles room with undefined participants gracefully', () => {
    // Mock chat room without participants
    const mockUsePage = jest.requireMock('@inertiajs/react').usePage;
    mockUsePage.mockReturnValue({
      props: {
        chatRooms: [
          {
            id: 1,
            name: 'Room Without Participants',
            type: 'direct',
            created_by: 1,
            // participants is undefined
            updated_at: '2025-08-22T10:00:00Z'
          }
        ],
        users: [],
        auth: { user: { id: 1, first_name: 'John', last_name: 'Doe', email: 'john@test.com' } }
      }
    });

    render(<Chat />);
    
    // Should render without crashing
    expect(screen.getByText('Room Without Participants')).toBeInTheDocument();
    expect(screen.getByText('U')).toBeInTheDocument(); // Fallback avatar
  });
});