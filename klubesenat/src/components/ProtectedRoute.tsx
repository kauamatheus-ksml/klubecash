import { ReactNode, useEffect, useState } from 'react';
import { useAuth } from '@/hooks/useAuth';
import { Loader2 } from 'lucide-react';

interface ProtectedRouteProps {
  children: ReactNode;
}

const ProtectedRoute: React.FC<ProtectedRouteProps> = ({ children }) => {
  const { user, isLoading } = useAuth();
  const [isInitializing, setIsInitializing] = useState(true);

  useEffect(() => {
    // Dar tempo para o useAuth processar o localStorage
    const timer = setTimeout(() => {
      setIsInitializing(false);
    }, 100);

    return () => clearTimeout(timer);
  }, []);

  if (isInitializing || isLoading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center">
        <div className="text-center space-y-6 max-w-md px-4">
          <div className="mx-auto w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center">
            <Loader2 className="h-8 w-8 text-white animate-spin" />
          </div>
          <div className="space-y-2">
            <h2 className="text-2xl font-bold text-gray-900">SestSenat Portal</h2>
            <p className="text-gray-600">Verificando autenticação...</p>
          </div>
          <div className="text-sm text-gray-500">
            Carregando dados da sua sessão
          </div>
        </div>
      </div>
    );
  }

  if (!user || user.senat !== 'Sim') {
    return (
      <div className="min-h-screen bg-gradient-to-br from-red-50 to-red-100 flex items-center justify-center">
        <div className="text-center space-y-6 max-w-md px-4">
          <div className="mx-auto w-16 h-16 bg-red-600 rounded-full flex items-center justify-center">
            <svg className="h-8 w-8 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
            </svg>
          </div>
          <div className="space-y-2">
            <h2 className="text-2xl font-bold text-gray-900">Acesso Negado</h2>
            <p className="text-gray-600">Você precisa estar logado no sistema principal do Klube Cash com uma conta válida do Senat.</p>
            <p className="text-sm text-gray-500">Estado do usuário: {user ? `${user.nome} (senat: ${user.senat})` : 'Não logado'}</p>
            <div className="text-xs text-gray-400 mt-2 p-2 bg-gray-100 rounded">
              <strong>Debug localStorage:</strong><br/>
              senat_user: {localStorage.getItem('senat_user') || 'null'}<br/>
              Quantidade de itens: {localStorage.length}<br/>
              URL atual: {window.location.href}
            </div>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <button
              onClick={() => window.location.href = 'http://localhost:8080/cliente/escolher-carteira'}
              className="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors text-sm"
            >
              Voltar ao Klube Cash
            </button>
            <button
              onClick={() => {
                const testUser = {
                  id: 123,
                  nome: "Usuário Teste Senat",
                  email: "teste@senat.com",
                  tipo: "cliente",
                  senat: "Sim",
                  status: "ativo"
                };
                localStorage.setItem('senat_user', JSON.stringify(testUser));
                window.location.reload();
              }}
              className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm"
            >
              Testar com Usuário Fake
            </button>
            <button
              onClick={() => {
                const realUser = {
                  id: 9,
                  nome: "Kaua Matheus da Silva Lope",
                  email: "kauamatheus920@gmail.com",
                  tipo: "cliente",
                  senat: "Sim",
                  status: "ativo"
                };
                localStorage.setItem('senat_user', JSON.stringify(realUser));
                window.location.reload();
              }}
              className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors text-sm"
            >
              Simular Usuário ID 9
            </button>
            <button
              onClick={() => {
                localStorage.clear();
                window.location.reload();
              }}
              className="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors text-sm"
            >
              Limpar localStorage
            </button>
          </div>
        </div>
      </div>
    );
  }

  return <>{children}</>;
};

export default ProtectedRoute;