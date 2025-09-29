import React, { useState, useEffect, createContext, useContext } from 'react';

export interface User {
  id: number;
  nome: string;
  email: string;
  tipo: string;
  senat: 'Sim' | 'Não';
  status: string;
}

interface AuthContextType {
  user: User | null;
  login: (email: string, password: string) => Promise<User>;
  logout: () => void;
  verifySession: (sessionData: any) => Promise<User | null>;
  isLoading: boolean;
  error: string | null;
  isAuthenticated: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

export const useAuthHook = () => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    // Verificar se há usuário salvo no localStorage
    const savedUser = localStorage.getItem('senat_user');
    console.log('SestSenat: Verificando localStorage senat_user:', savedUser);

    if (savedUser) {
      try {
        const parsedUser = JSON.parse(savedUser);
        console.log('SestSenat: Dados do usuário parseados:', parsedUser);

        if (parsedUser.senat === 'Sim') {
          console.log('SestSenat: ✅ Usuário válido do Senat, fazendo login automático');
          setUser(parsedUser);
        } else {
          console.log('SestSenat: ❌ Usuário não é do Senat, removendo localStorage');
          localStorage.removeItem('senat_user');
        }
      } catch (error) {
        console.error('SestSenat: ❌ Erro ao recuperar usuário salvo:', error);
        localStorage.removeItem('senat_user');
      }
    } else {
      console.log('SestSenat: ❌ Nenhum usuário salvo no localStorage');
    }
  }, []);

  const login = async (email: string, password: string): Promise<User> => {
    setIsLoading(true);
    setError(null);

    try {
      console.log('SestSenat: Tentando login com:', { email });

      // Fazer chamada para a API de login
      const response = await fetch('http://localhost:8080/api/auth/login-senat.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({ email, password }),
      });

      const data = await response.json();
      console.log('SestSenat: Resposta da API:', data);

      if (!data.success) {
        throw new Error(data.message || 'Erro no login');
      }

      const userData: User = {
        id: data.user.id,
        nome: data.user.nome,
        email: data.user.email,
        tipo: data.user.tipo,
        senat: data.user.senat,
        status: data.user.status
      };

      console.log('SestSenat: Login bem-sucedido, salvando dados:', userData);

      // Salvar no localStorage para futuras visitas
      localStorage.setItem('senat_user', JSON.stringify(userData));
      setUser(userData);

      return userData;
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Erro desconhecido';
      console.error('SestSenat: Erro no login:', errorMessage);
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
    window.location.href = 'https://klubecash.com';
  };

  const verifySession = async (sessionData: any): Promise<User | null> => {
    setIsLoading(true);
    setError(null);

    try {
      console.log('Verificando dados da sessão:', sessionData);

      // Verificar se os dados da sessão são válidos
      if (sessionData && sessionData.senat === 'Sim') {
        const userData: User = {
          id: sessionData.id || 0,
          nome: sessionData.nome || sessionData.name || '',
          email: sessionData.email || '',
          tipo: sessionData.tipo || 'cliente',
          senat: sessionData.senat,
          status: sessionData.status || 'ativo'
        };

        console.log('Dados do usuário processados:', userData);
        localStorage.setItem('senat_user', JSON.stringify(userData));
        setUser(userData);
        console.log('Usuário definido no estado, login bem-sucedido');
        return userData;
      }

      console.log('Dados da sessão inválidos ou usuário não é do Senat');
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

// AuthProvider Component
interface AuthProviderProps {
  children: React.ReactNode;
}

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
  const auth = useAuthHook();

  return (
    <AuthContext.Provider value={auth}>
      {children}
    </AuthContext.Provider>
  );
};