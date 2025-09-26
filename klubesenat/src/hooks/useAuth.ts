import { useState, useEffect } from 'react';

export interface User {
  id: number;
  nome: string;
  email: string;
  tipo: string;
  senat: 'Sim' | 'Não';
  status: string;
}

// Interface removida do hook - agora está no AuthProvider

export const useAuthHook = () => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    // Verificar se há usuário salvo no localStorage
    const savedUser = localStorage.getItem('senat_user');
    if (savedUser) {
      try {
        const parsedUser = JSON.parse(savedUser);
        if (parsedUser.senat === 'Sim') {
          setUser(parsedUser);
        } else {
          localStorage.removeItem('senat_user');
        }
      } catch (error) {
        console.error('Erro ao recuperar usuário salvo:', error);
        localStorage.removeItem('senat_user');
      }
    }
  }, []);

  const login = async (email: string, password: string): Promise<User> => {
    setIsLoading(true);
    setError(null);

    try {
      // Fazer requisição para o sistema principal do Klube Cash via proxy
      const response = await fetch('/api/auth/login-senat', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          email,
          password,
          require_senat: true
        }),
        credentials: 'include'
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Erro ao fazer login');
      }

      if (!data.success) {
        throw new Error(data.message || 'Login falhou');
      }

      const userData = data.user;

      // Verificar se o usuário é do Senat
      if (userData.senat !== 'Sim') {
        throw new Error('Acesso negado: Apenas usuários do Senat podem acessar este sistema');
      }

      // Salvar usuário no localStorage e no estado
      localStorage.setItem('senat_user', JSON.stringify(userData));
      setUser(userData);

      return userData;
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Erro desconhecido';
      setError(errorMessage);
      throw new Error(errorMessage);
    } finally {
      setIsLoading(false);
    }
  };

  const logout = () => {
    localStorage.removeItem('senat_user');
    setUser(null);
    setError(null);

    // Redirecionar para o Klube Cash
    window.location.href = 'https://klubecash.com/cliente/escolher-carteira';
  };

  const verifySession = async (sessionId: string, email: string): Promise<User | null> => {
    setIsLoading(true);
    setError(null);

    try {
      const response = await fetch('/api/auth/verify-session-senat', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          session_id: sessionId,
          email,
          require_senat: true
        }),
        credentials: 'include'
      });

      const data = await response.json();

      if (data.success && data.user.senat === 'Sim') {
        localStorage.setItem('senat_user', JSON.stringify(data.user));
        setUser(data.user);
        return data.user;
      }

      return null;
    } catch (error) {
      console.error('Erro ao verificar sessão:', error);
      return null;
    } finally {
      setIsLoading(false);
    }
  };

  return {
    user,
    login,
    logout,
    verifySession,
    isLoading,
    error,
    isAuthenticated: !!user && user.senat === 'Sim'
  };
};