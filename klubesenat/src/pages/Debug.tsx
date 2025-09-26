import { useState, useEffect } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { useAuth } from "@/hooks/useAuth";

const Debug = () => {
  const { user, verifySession } = useAuth();
  const [localStorageData, setLocalStorageData] = useState<string | null>(null);

  useEffect(() => {
    // Verificar localStorage ao carregar
    const data = localStorage.getItem('senat_user');
    setLocalStorageData(data);
  }, []);

  const createTestUser = () => {
    const testUser = {
      id: 123,
      nome: "Usuário Teste Senat",
      email: "teste@senat.com",
      tipo: "cliente",
      senat: "Sim",
      status: "ativo"
    };

    localStorage.setItem('senat_user', JSON.stringify(testUser));
    setLocalStorageData(JSON.stringify(testUser));
    console.log('Usuário teste criado:', testUser);
  };

  const clearLocalStorage = () => {
    localStorage.removeItem('senat_user');
    setLocalStorageData(null);
    console.log('localStorage limpo');
  };

  const testVerifySession = async () => {
    if (localStorageData) {
      try {
        const userData = JSON.parse(localStorageData);
        await verifySession(userData);
      } catch (error) {
        console.error('Erro ao testar verifySession:', error);
      }
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 p-8">
      <div className="max-w-4xl mx-auto space-y-6">
        <h1 className="text-3xl font-bold">Debug SestSenat</h1>

        <Card>
          <CardHeader>
            <CardTitle>Estado do Usuário</CardTitle>
            <CardDescription>Estado atual da autenticação</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div>
                <strong>Usuário logado:</strong> {user ? 'Sim' : 'Não'}
              </div>
              {user && (
                <div className="bg-green-50 p-4 rounded">
                  <pre>{JSON.stringify(user, null, 2)}</pre>
                </div>
              )}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>localStorage</CardTitle>
            <CardDescription>Dados salvos no navegador</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div>
                <strong>Dados no localStorage:</strong>
              </div>
              {localStorageData ? (
                <div className="bg-blue-50 p-4 rounded">
                  <pre>{localStorageData}</pre>
                </div>
              ) : (
                <div className="text-gray-500">Nenhum dado salvo</div>
              )}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Ações de Teste</CardTitle>
            <CardDescription>Ferramentas para testar o sistema</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex space-x-4">
              <Button onClick={createTestUser}>
                Criar Usuário Teste
              </Button>
              <Button onClick={clearLocalStorage} variant="outline">
                Limpar localStorage
              </Button>
              <Button onClick={testVerifySession} variant="secondary">
                Testar verifySession
              </Button>
              <Button onClick={() => window.location.reload()} variant="ghost">
                Recarregar Página
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
};

export default Debug;